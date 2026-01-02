import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import apiClient from './api/client';
import BusinessProfileForm from './components/BusinessProfile/BusinessProfileForm';
import RequireProfile from './components/Auth/RequireProfile';
import './App.css';

// Placeholder Login Component
const Login = () => {
  const handleLogin = async () => {
    // Simulating login for demo purposes - in real app, use a real form
    // This expects the backend to be running and a valid user to exist or be created
    // For now, we just redirect hoping there's a token or we need to implement a real login form.
    // But since the user asked for the Business Profile flow, I'll assume they might already have a token or I'll provide a basic input.

    const email = prompt("Enter email test user:");
    const password = prompt("Enter password:");
    if (email && password) {
      try {
        const response = await apiClient.login(email, password);
        localStorage.setItem('auth_token', response.data.token);
        window.location.href = '/dashboard';
      } catch (e) {
        alert("Login failed");
      }
    }
  };

  return (
    <div className="flex flex-col items-center justify-center h-screen bg-gray-100">
      <h1 className="text-2xl font-bold mb-4">Login</h1>
      <button onClick={handleLogin} className="px-4 py-2 bg-blue-600 text-white rounded">
        Login via Prompt
      </button>
    </div>
  );
};

const Dashboard = () => (
  <div className="p-8">
    <h1 className="text-2xl font-bold">Dashboard</h1>
    <p>Welcome! You have completed your business profile.</p>
    <button onClick={() => {
      apiClient.logout().then(() => {
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
      });
    }} className="mt-4 px-4 py-2 bg-red-500 text-white rounded">Logout</button>
  </div>
);

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />

        {/* Route for Business Profile Creation - Accessible if logged in but no profile? 
            Actually RequireProfile redirects TO here. So this route itself shouldn't be wrapped in RequireProfile check regarding profile existence,
            but SHOULD be wrapped in Auth check. For simplicity, assuming apiClient handles 401 redirects. 
         */}
        <Route path="/business-profile" element={<BusinessProfileForm onSuccess={() => window.location.href = '/dashboard'} />} />

        {/* Protected Routes */}
        <Route element={<RequireProfile />}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/invoices" element={<div>Invoices Page Placeholder</div>} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

export default App;
