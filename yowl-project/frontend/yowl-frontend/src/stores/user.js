import { defineStore } from 'pinia';
import api from '@/services/apiService';

/**
 * Stockage du compte selon « se souvenir de moi ».
 *
 * Coché, la session survit à la fermeture du navigateur ; décoché, elle
 * s'arrête avec l'onglet. La case était envoyée au serveur et lue par
 * personne, et le jeton restait de toute façon dans localStorage.
 */
const REMEMBER_KEY = 'yowl.remember';

const authStorage = {
  getItem(key) {
    return localStorage.getItem(key) ?? sessionStorage.getItem(key);
  },
  setItem(key, value) {
    const remembered = localStorage.getItem(REMEMBER_KEY) === '1';
    if (remembered) {
      localStorage.setItem(key, value);
      sessionStorage.removeItem(key);
    } else {
      sessionStorage.setItem(key, value);
      localStorage.removeItem(key);
    }
  },
  removeItem(key) {
    localStorage.removeItem(key);
    sessionStorage.removeItem(key);
  },
};

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    token: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isAdmin: (state) => {
      if (!state.user?.roles) return false;
      return state.user.roles.some(role => 
        typeof role === 'string' ? role === 'admin' : role.name === 'admin'
      );
    },
  },
  actions: {
    //user resgitration
    async registerUser(data) {
      
      try {
        const result = await api.post('/register', {
          fullname: `${data.firstname} ${data.lastname}`,
          username: data.username,
          email: data.email,
          birthdate: data.birthdate,
          password: data.password,
          password_confirmation: data.password_confirmation,
        });
        
        return result.data;
      } catch (err) {
        let message = 'Registration failed';
        if (err.response && err.response.data) {
          message = err.response.data.message || JSON.stringify(err.response.data.error);
        }
        throw new Error(message);
      }
    },

    // resend verification code (OTP)
    async resendVerificationCode(email) {
      try {
        const res = await api.post('/email/otp/resend', { email });
        return res.data;
      } catch (err) {
        throw new Error(err.response?.data?.message || 'Cannot resend code');
      }
    },
    // verify OTP code
    async verifyEmailCode(payload) {
      // { email, code }
      try {
        const res = await api.post('/email/otp/verify', payload);
        return res.data;
      } catch (err) {
        throw new Error(err.response?.data?.message || 'Verification failed');
      }
    },

    //user login
    async loginUser(data) {
      try {
        const remember = Boolean(data.rememberMe);
        // Le choix est posé avant l'écriture du store, pour que la
        // persistance vise le bon stockage dès la première sauvegarde.
        localStorage.setItem(REMEMBER_KEY, remember ? '1' : '0');

        const result = await api.post('/login', {
          email: data.identifier,
          password: data.password,
          remember,
        });

        this.user = result.data.user;
        this.token = result.data.token;

        return result.data;
      } catch (err) {
        // Le code accompagne le message. Il dit lequel des refus s'est
        // produit, ce que la vue doit savoir pour ouvrir la fenêtre de
        // vérification plutôt que d'afficher une simple erreur. Elle le
        // déduisait jusqu'ici en comparant le texte anglais du message.
        const donnees = err.response?.data;

        const erreur = new Error(
          donnees?.message
            // Sans réponse du tout, c'est le réseau ou le serveur qui manque,
            // pas les identifiants : le dire évite de chercher une faute de
            // frappe dans un mot de passe correct.
            ?? (err.response
              ? 'La connexion a échoué. Réessayez dans un instant.'
              : 'Le service ne répond pas. Vérifiez votre connexion, puis réessayez.')
        );
        erreur.code = donnees?.code ?? (err.response ? 'erreur_serveur' : 'reseau_indisponible');

        throw erreur;
      }
    },

    //get current user
    async getCurrentUser() {
      if (!this.token) return null;

      try {
        const result = await api.get('/user', {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        });
        this.user = result.data;
        
        return result.data;
      } catch (err) {
        // Silent error;
        this.logoutUser();
        return null;
      }
    },

    //update user
    async updateUser(data) {
      if (!this.token) throw new Error('Not authenticated');
      if (!this.user) throw new Error('No user loaded');

      try {
        
        
        const result = await api.post(
          `/users/${this.user.id}`,
          {
            username: data.username,
            fullname: data.fullname,
            email: data.email,
            password: data.newPassword ? data.newPassword : undefined,
            picture: data.picture ? data.picture : undefined,
          },
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
              'Content-Type': 'multipart/form-data',
            },
          }
        );

        // La réponse est enveloppée : { success, data, message }
        this.user = result.data.data ?? result.data;

        return result.data;
      } catch (err) {
        let message = 'Update failed';
        if (err.response && err.response.data) {
          message = err.response.data.message || JSON.stringify(err.response.data.error);
        }
        throw new Error(message);
      }
    },

    //user logout
    async logoutUser() {
      try {
        await api.post('/logout');
      } catch {
        // Silent error handling
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem(REMEMBER_KEY);
    },

    async leaveCommunity() {
      try {
        await api.delete(`/users/${this.user.id}`);
      } catch (err) {
        throw new Error('Failed to delete account');
      }
      this.user = null;
      this.token = null;
    },
    
    //init store on mounted
    async initUser() {
      if (this.token && !this.user) {
        await this.getCurrentUser();
      }
    },
  },
  persist: { storage: authStorage },
});
