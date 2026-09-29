// WorkLink Admin Auth Management
const TOKEN_KEY = 'worklink_admin_token';
const USER_KEY = 'worklink_admin_user';

export const Auth = {
  getToken() {
    return localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY);
  },

  setToken(token, persist = true) {
    if (persist) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      sessionStorage.setItem(TOKEN_KEY, token);
    }
  },

  getUser() {
    try {
      const raw = localStorage.getItem(USER_KEY) || sessionStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  },

  setUser(user, persist = true) {
    const data = JSON.stringify(user);
    if (persist) {
      localStorage.setItem(USER_KEY, data);
    } else {
      sessionStorage.setItem(USER_KEY, data);
    }
  },

  clear() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(USER_KEY);
  },

  isAuthenticated() {
    return Boolean(this.getToken());
  },
};
