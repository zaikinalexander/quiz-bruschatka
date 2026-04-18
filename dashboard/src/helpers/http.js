import Axios from 'axios';

const axios = Axios.create({
  baseURL: '/api',
});

axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('admin:unauthorized'));
    }

    return Promise.reject(error);
  },
);

export default axios;
