import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import * as yup from 'yup';
import { yupResolver } from '@hookform/resolvers/yup';
import apiClient from '../../api/client';
import { BusinessProfile } from '../../types/auth';

const schema = yup.object({
    company_name: yup.string().required('Company Name is required'),
    registration_number: yup.string().required('Registration Number is required'),
    tax_identification_number: yup.string().optional(),
    phone: yup.string().required('Phone number is required'),
    address_line_1: yup.string().required('Address Line 1 is required'),
    address_line_2: yup.string().optional(),
    city: yup.string().required('City is required'),
    state: yup.string().required('State is required'),
    postal_code: yup.string().required('Postal Code is required'),
    country: yup.string().required('Country is required'),
    website: yup.string().nullable().optional(), // Simplified for now to avoid complex yup logic types
});

// Explicit interface to avoid inference issues
interface FormData {
    company_name: string;
    registration_number: string;
    tax_identification_number?: string | null;
    phone: string;
    address_line_1: string;
    address_line_2?: string | null;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    website?: string | null;
}

interface Props {
    initialData?: BusinessProfile | null;
    onSuccess?: () => void;
}

const BusinessProfileForm: React.FC<Props> = ({ initialData, onSuccess }) => {
    const [error, setError] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
        resolver: yupResolver(schema) as any, // Temporary cast to silence strict resolver type mismatch
        defaultValues: {
            company_name: initialData?.company_name || '',
            registration_number: initialData?.registration_number || '',
            tax_identification_number: initialData?.tax_identification_number || '',
            phone: initialData?.phone || '',
            address_line_1: initialData?.address_line_1 || '',
            address_line_2: initialData?.address_line_2 || '',
            city: initialData?.city || '',
            state: initialData?.state || '',
            postal_code: initialData?.postal_code || '',
            country: initialData?.country || '',
            website: initialData?.website || '',
        }
    });

    const onSubmit = async (data: FormData) => {
        setIsSubmitting(true);
        setError(null);
        try {
            await apiClient.createOrUpdateBusinessProfile(data);
            if (onSuccess) {
                onSuccess();
            } else {
                window.location.href = '/dashboard'; // Default redirect
            }
        } catch (err: any) {
            console.error(err);
            setError(err.response?.data?.message || 'Failed to save profile');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div className="sm:mx-auto sm:w-full sm:max-w-md">
                <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Complete your Business Profile
                </h2>
                <p className="mt-2 text-center text-sm text-gray-600">
                    Please provide your business details to continue.
                </p>
            </div>

            <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
                <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form className="space-y-6" onSubmit={handleSubmit(onSubmit)}>
                        {error && (
                            <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                                <div className="flex">
                                    <div className="ml-3">
                                        <p className="text-sm text-red-700">{error}</p>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div className="sm:col-span-6">
                                <label htmlFor="company_name" className="block text-sm font-medium text-gray-700">
                                    Company Name
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="company_name"
                                        type="text"
                                        {...register('company_name')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.company_name && <p className="mt-1 text-sm text-red-600">{errors.company_name.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-3">
                                <label htmlFor="registration_number" className="block text-sm font-medium text-gray-700">
                                    Business Registration No.
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="registration_number"
                                        type="text"
                                        {...register('registration_number')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.registration_number && <p className="mt-1 text-sm text-red-600">{errors.registration_number.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-3">
                                <label htmlFor="tax_identification_number" className="block text-sm font-medium text-gray-700">
                                    TIN (Optional)
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="tax_identification_number"
                                        type="text"
                                        {...register('tax_identification_number')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <div className="sm:col-span-6">
                                <label htmlFor="address_line_1" className="block text-sm font-medium text-gray-700">
                                    Address Line 1
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="address_line_1"
                                        type="text"
                                        {...register('address_line_1')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.address_line_1 && <p className="mt-1 text-sm text-red-600">{errors.address_line_1.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-6">
                                <label htmlFor="address_line_2" className="block text-sm font-medium text-gray-700">
                                    Address Line 2
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="address_line_2"
                                        type="text"
                                        {...register('address_line_2')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                </div>
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="city" className="block text-sm font-medium text-gray-700">
                                    City
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="city"
                                        type="text"
                                        {...register('city')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.city && <p className="mt-1 text-sm text-red-600">{errors.city.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="state" className="block text-sm font-medium text-gray-700">
                                    State
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="state"
                                        type="text"
                                        {...register('state')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.state && <p className="mt-1 text-sm text-red-600">{errors.state.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="postal_code" className="block text-sm font-medium text-gray-700">
                                    Postal Code
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="postal_code"
                                        type="text"
                                        {...register('postal_code')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.postal_code && <p className="mt-1 text-sm text-red-600">{errors.postal_code.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-3">
                                <label htmlFor="country" className="block text-sm font-medium text-gray-700">
                                    Country
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="country"
                                        type="text"
                                        {...register('country')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.country && <p className="mt-1 text-sm text-red-600">{errors.country.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-3">
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
                                    Phone Number
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="phone"
                                        type="text"
                                        {...register('phone')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.phone && <p className="mt-1 text-sm text-red-600">{errors.phone.message}</p>}
                                </div>
                            </div>

                            <div className="sm:col-span-6">
                                <label htmlFor="website" className="block text-sm font-medium text-gray-700">
                                    Website (Optional)
                                </label>
                                <div className="mt-1">
                                    <input
                                        id="website"
                                        type="text"
                                        {...register('website')}
                                        className="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    />
                                    {errors.website && <p className="mt-1 text-sm text-red-600">{errors.website.message}</p>}
                                </div>
                            </div>

                        </div>

                        <div className="mt-6 flex items-center justify-end">
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className={`w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${isSubmitting ? 'opacity-70 cursor-not-allowed' : ''}`}
                            >
                                {isSubmitting ? 'Saving...' : 'Save Profile'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default BusinessProfileForm;
