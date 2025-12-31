import { defineStore } from 'pinia';
import api from '@/services/apiService';

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
        const result = await api.post('/login', {
          email: data.identifier,
          password: data.password,
        });

        this.user = result.data.user;
        this.token = result.data.token;

        return result.data;
      } catch (err) {
        throw new Error(
          err.response && err.response.data && err.response.data.message
            ? err.response.data.message
            : 'Login failed'
        );
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
            picture: data.picture ? data.picture : null,
          },
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
              'Content-Type': 'multipart/form-data',
            },
          }
        );

        this.user = result.data;
        

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
  persist: true,
});
