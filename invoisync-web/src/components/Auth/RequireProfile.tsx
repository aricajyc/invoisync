import React, { useEffect, useState } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import apiClient from '../../api/client';
import { User } from '../../types/auth';

const RequireProfile: React.FC = () => {
    const [isLoading, setIsLoading] = useState(true);
    const [user, setUser] = useState<User | null>(null);
    const location = useLocation();

    useEffect(() => {
        const checkUser = async () => {
            try {
                const response = await apiClient.getMe();
                setUser(response.data);
            } catch (error) {
                console.error("Failed to fetch user", error);
                // If 401, apiClient interceptor will likely handle it.
                // But if we are here and getting other errors, might mean not logged in or server error.
            } finally {
                setIsLoading(false);
            }
        };
        checkUser();
    }, []);

    if (isLoading) {
        return (
            <div className="flex justify-center items-center h-screen">
                <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
            </div>
        );
    }

    if (!user) {
        // Not authenticated
        return <Navigate to="/login" state={{ from: location }} replace />;
    }

    if (!user.business_profile) {
        // Logged in but no profile
        return <Navigate to="/business-profile" replace />;
    }

    return <Outlet />;
};

export default RequireProfile;
