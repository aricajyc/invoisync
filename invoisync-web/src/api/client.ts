import axios, { AxiosInstance } from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Request interceptor for adding auth token
    this.client.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor for handling errors
    this.client.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('auth_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  // Invoice endpoints
  getInvoices(params?: any) {
    return this.client.get('/invoices', { params });
  }

  getInvoice(id: number) {
    return this.client.get(`/invoices/${id}`);
  }

  createInvoice(data: any) {
    return this.client.post('/invoices', data);
  }

  updateInvoice(id: number, data: any) {
    return this.client.put(`/invoices/${id}`, data);
  }

  deleteInvoice(id: number) {
    return this.client.delete(`/invoices/${id}`);
  }

  validateInvoice(id: number, validationType: string) {
    return this.client.post(`/invoices/${id}/validate`, { validation_type: validationType });
  }

  submitToMyInvois(id: number, digitalSignature: string) {
    return this.client.post(`/invoices/${id}/submit`, { 
      submission_type: 'single',
      digital_signature: digitalSignature 
    });
  }

  duplicateInvoice(id: number) {
    return this.client.post(`/invoices/${id}/duplicate`);
  }

  // Bulk upload endpoints
  uploadBulkFile(file: File) {
    const formData = new FormData();
    formData.append('file', file);
    return this.client.post('/bulk-upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  }

  getBulkBatch(id: number) {
    return this.client.get(`/bulk-upload/${id}`);
  }

  downloadTemplate() {
    return this.client.get('/bulk-upload/template', { responseType: 'blob' });
  }

  // Dashboard endpoints
  getDashboardOverview(period: string) {
    return this.client.get('/dashboard/overview', { params: { period } });
  }

  getAnalytics(dateFrom: string, dateTo: string) {
    return this.client.get('/dashboard/analytics', { 
      params: { date_from: dateFrom, date_to: dateTo } 
    });
  }

  // Auth endpoints
  login(email: string, password: string) {
    return this.client.post('/login', { email, password });
  }

  logout() {
    return this.client.post('/logout');
  }

  getMe() {
    return this.client.get('/me');
  }
}

export default new ApiClient();