import { defineStore } from 'pinia';
import api, { fetchToken } from '@/services/apiService';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isAdmin: (state) => state.user && state.user.roles && state.user.roles.includes('admin'),
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
      } catch (error) {
        let message = 'Registration failed';
        if (error.response && error.response.data) {
          message = error.response.data.message || JSON.stringify(error.response.data.error);
        }
        throw new Error(message);
      }
    },

    // resend verification code (OTP)
    async resendVerificationCode(email) {
      try {
        const res = await api.post('/email/otp/resend', { email });
        return res.data;
      } catch (error) {
        throw new Error(error.response?.data?.message || 'Cannot resend code');
      }
    },
    // verify OTP code
    async verifyEmailCode(payload) {
      // { email, code }
      try {
        const res = await api.post('/email/otp/verify', payload);
        return res.data;
      } catch (error) {
        throw new Error(error.response?.data?.message || 'Verification failed');
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

        // if remember me, stock  token in localStorage
        if (data.rememberMe && this.token) {
          // use cookie instead of localStorage
          localStorage.setItem('token', this.token);
          
        }

        return result.data;
      } catch (error) {
        throw new Error(
          error.response && error.response.data && error.response.data.message
            ? error.response.data.message
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
      } catch (error) {
        console.log(error);
        this.logoutUser();
        return null;
      }
    },

    //update user
    async updateUser(data) {
      if (!this.token) throw new Error('Not authenticated');
      if (!this.user) throw new Error('No user loaded');

      try {
        await fetchToken();
        
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
      } catch (error) {
        console.error('Update failed', error);
        let message = 'Update failed';
        if (error.response && error.response.data) {
          message = error.response.data.message || JSON.stringify(error.response.data.error);
        }
        throw new Error(message);
      }
    },

    //user logout
    async logoutUser() {
      try {
        await api.post(
          '/logout',
          {},
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
      } catch (error) {
        console.log('Error : ', error);
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
    },

    async leaveCommunity() {
      try {
        await api.delete(
          `/users/${this.user.id}`,
          {},
          {
            headers: {
              Authorization: `Bearer ${this.token}`,
            },
          }
        );
      } catch (error) {
        console.log('Error : ', error);
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
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
