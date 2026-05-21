import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';
import axios from 'axios';
import SimpleCombobox from '@/Components/SimpleCombobox';

export default function BusinessProfileForm({ auth, mustVerifyEmail, status, profile }) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        business_name: profile?.business_name || '',
        business_registration_number: profile?.business_registration_number || '',
        tax_identification_number: profile?.tax_identification_number || '',
        sst_registration_number: profile?.sst_registration_number || '',
        tourism_tax_registration_number: profile?.tourism_tax_registration_number || '',
        msic_code: profile?.msic_code || '',
        business_activity_description: profile?.business_activity_description || '',
        contact_phone: profile?.contact_phone || '',
        contact_email: profile?.contact_email || auth.user.email,
        address_line_0: profile?.address_line_0 || '',
        address_line_1: profile?.address_line_1 || '',
        address_line_2: profile?.address_line_2 || '',
        city: profile?.city || '',
        state: profile?.state || '',
        postal_zone: profile?.postal_zone || '',
        country: profile?.country || 'MYS',
        myinvois_client_id: profile?.myinvois_client_id || '',
        myinvois_client_secret: profile?.myinvois_client_secret || '',
    });

    const [countries, setCountries] = useState([]);
    const [states, setStates] = useState([]);
    const [msicCodes, setMsicCodes] = useState([]);
    const [loadingCodes, setLoadingCodes] = useState(true);

    const [validatingTin, setValidatingTin] = useState(false);
    const [tinResult, setTinResult] = useState(null);

    useEffect(() => {
        const fetchCodes = async () => {
            try {
                const [cRes, sRes, mRes] = await Promise.all([
                    axios.get(route('ref.countries')),
                    axios.get(route('ref.states')),
                    axios.get(route('ref.msic-codes'))
                ]);
                setCountries(cRes.data);
                setStates(sRes.data);
                // Transform MSIC object to array if needed, assuming simple array or handling structure
                // Common structure for MSIC might be code -> desc. Let's assume list or map.
                // If it is a list of objects { Code, Description, ... }
                setMsicCodes(Array.isArray(mRes.data) ? mRes.data : Object.values(mRes.data));
            } catch (error) {
                console.error("Failed to load reference data", error);
            } finally {
                setLoadingCodes(false);
            }
        };
        fetchCodes();
    }, []);

    const handleValidateTin = async () => {
        if (!data.tax_identification_number || !data.business_registration_number) {
            setTinResult({ valid: false, message: 'Please enter both TIN and Registration Number first.' });
            return;
        }
        if (!data.myinvois_client_id || !data.myinvois_client_secret) {
            setTinResult({ valid: false, message: 'Please scroll down and enter your MyInvois Client ID and Secret first.' });
            return;
        }
        setValidatingTin(true);
        setTinResult(null);
        try {
            const res = await axios.post(route('business-profile.validate-tin'), {
                tin: data.tax_identification_number,
                registration_number: data.business_registration_number,
                client_id: data.myinvois_client_id,
                client_secret: data.myinvois_client_secret,
            });
            setTinResult(res.data);
        } catch (error) {
            setTinResult({ valid: false, message: 'An unexpected error occurred.' });
        } finally {
            setValidatingTin(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('business-profile.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Business Profile</h2>}
        >
            <Head title="Business Profile" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <section className="max-w-2xl">
                            <header>
                                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Business Information</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Please provide your business details as registered with IRBM.
                                </p>
                            </header>

                            {!profile?.business_name && (
                                <div className="mb-4 rounded-md bg-yellow-50 p-4 mt-6 dark:bg-yellow-900/50">
                                    <div className="flex">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">Attention needed</h3>
                                            <div className="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                <p>Before you can start creating invoices, you must complete your business profile setup.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={submit} className="mt-6 space-y-6">
                                {/* Basic Info */}
                                <div>
                                    <InputLabel htmlFor="business_name" value="Business Name" />
                                    <TextInput
                                        id="business_name"
                                        className="mt-1 block w-full"
                                        value={data.business_name}
                                        onChange={(e) => setData('business_name', e.target.value)}
                                        required
                                        isFocused
                                    />
                                    <InputError className="mt-2" message={errors.business_name} />
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="business_registration_number" value="Registration Number (BRN)" />
                                        <TextInput
                                            id="business_registration_number"
                                            className="mt-1 block w-full"
                                            value={data.business_registration_number}
                                            onChange={(e) => setData('business_registration_number', e.target.value)}
                                            required
                                        />
                                        <InputError className="mt-2" message={errors.business_registration_number} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="tax_identification_number" value="Tax ID (TIN)" />
                                        <div className="flex space-x-2 mt-1">
                                            <TextInput
                                                id="tax_identification_number"
                                                className="block w-full"
                                                value={data.tax_identification_number}
                                                onChange={(e) => setData('tax_identification_number', e.target.value)}
                                                required
                                                placeholder="C1234567890"
                                            />
                                            <button 
                                                type="button" 
                                                onClick={handleValidateTin}
                                                disabled={validatingTin}
                                                className="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-25 transition ease-in-out duration-150"
                                            >
                                                {validatingTin ? 'Validating...' : 'Validate'}
                                            </button>
                                        </div>
                                        <InputError className="mt-2" message={errors.tax_identification_number} />
                                        {tinResult && (
                                            <div className={`mt-2 text-sm ${tinResult.valid ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
                                                {tinResult.message}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="sst_registration_number" value="SST Number (Optional)" />
                                        <TextInput
                                            id="sst_registration_number"
                                            className="mt-1 block w-full"
                                            value={data.sst_registration_number}
                                            onChange={(e) => setData('sst_registration_number', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.sst_registration_number} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="tourism_tax_registration_number" value="Tourism Tax No. (Optional)" />
                                        <TextInput
                                            id="tourism_tax_registration_number"
                                            className="mt-1 block w-full"
                                            value={data.tourism_tax_registration_number}
                                            onChange={(e) => setData('tourism_tax_registration_number', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.tourism_tax_registration_number} />
                                    </div>
                                </div>

                                {/* Classifications */}
                                <div>
                                    <InputLabel htmlFor="msic_code" value="Business Activity (MSIC)" />
                                    <div className="text-xs text-gray-500 mb-1">Search by description, code will be auto-filled.</div>
                                    <SimpleCombobox
                                        value={data.msic_code}
                                        onChange={(val) => {
                                            const selected = msicCodes.find(m => m.Code === val);
                                            setData((prev) => ({
                                                ...prev,
                                                msic_code: val,
                                                business_activity_description: selected ? selected.Description : prev.business_activity_description
                                            }));
                                        }}
                                        options={msicCodes}
                                        displayKey="Description"
                                        valueKey="Code"
                                        placeholder="Search Business Activity..."
                                    />
                                    <InputError className="mt-2" message={errors.msic_code} />
                                </div>

                                <div className="hidden">
                                    <InputLabel htmlFor="business_activity_description" value="Business Activity Description" />
                                    <TextInput
                                        id="business_activity_description"
                                        className="mt-1 block w-full"
                                        value={data.business_activity_description}
                                        readOnly
                                    />
                                    <InputError className="mt-2" message={errors.business_activity_description} />
                                </div>

                                {/* Contact */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="contact_email" value="Contact Email" />
                                        <TextInput
                                            id="contact_email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            value={data.contact_email}
                                            onChange={(e) => setData('contact_email', e.target.value)}
                                            required
                                        />
                                        <InputError className="mt-2" message={errors.contact_email} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="contact_phone" value="Contact Phone" />
                                        <TextInput
                                            id="contact_phone"
                                            className="mt-1 block w-full"
                                            value={data.contact_phone}
                                            onChange={(e) => setData('contact_phone', e.target.value)}
                                            required
                                        />
                                        <InputError className="mt-2" message={errors.contact_phone} />
                                    </div>
                                </div>

                                {/* Address */}
                                <div className="space-y-4 border-t pt-4">
                                    <h3 className="text-md font-medium text-gray-900 dark:text-gray-100">Address</h3>

                                    <div>
                                        <InputLabel htmlFor="address_line_0" value="Address Line 0" />
                                        <TextInput
                                            id="address_line_0"
                                            className="mt-1 block w-full"
                                            value={data.address_line_0}
                                            onChange={(e) => setData('address_line_0', e.target.value)}
                                            required
                                        />
                                        <InputError className="mt-2" message={errors.address_line_0} />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="address_line_1" value="Address Line 1" />
                                        <TextInput
                                            id="address_line_1"
                                            className="mt-1 block w-full"
                                            value={data.address_line_1}
                                            onChange={(e) => setData('address_line_1', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.address_line_1} />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="address_line_2" value="Address Line 2" />
                                        <TextInput
                                            id="address_line_2"
                                            className="mt-1 block w-full"
                                            value={data.address_line_2}
                                            onChange={(e) => setData('address_line_2', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.address_line_2} />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="postal_zone" value="Postal Zone" />
                                            <TextInput
                                                id="postal_zone"
                                                className="mt-1 block w-full"
                                                value={data.postal_zone}
                                                onChange={(e) => setData('postal_zone', e.target.value)}
                                                required
                                            />
                                            <InputError className="mt-2" message={errors.postal_zone} />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="city" value="City" />
                                            <TextInput
                                                id="city"
                                                className="mt-1 block w-full"
                                                value={data.city}
                                                onChange={(e) => setData('city', e.target.value)}
                                                required
                                            />
                                            <InputError className="mt-2" message={errors.city} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="state" value="State" />
                                            <select
                                                id="state"
                                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                value={data.state}
                                                onChange={(e) => setData('state', e.target.value)}
                                                required
                                            >
                                                <option value="">Select State</option>
                                                {states.map((s) => (
                                                    <option key={s.Code} value={s.Code}>{s.State}</option>
                                                ))}
                                            </select>
                                            <InputError className="mt-2" message={errors.state} />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="country" value="Country" />
                                            <select
                                                id="country"
                                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                value={data.country}
                                                onChange={(e) => setData('country', e.target.value)}
                                                required
                                            >
                                                <option value="">Select Country</option>
                                                {countries.map((c) => (
                                                    <option key={c.Code} value={c.Code}>{c.Country}</option>
                                                ))}
                                            </select>
                                            <InputError className="mt-2" message={errors.country} />
                                        </div>
                                    </div>
                                </div>

                                {/* MyInvois Credentials */}
                                <div className="space-y-4 border-t pt-4">
                                    <h3 className="text-md font-medium text-gray-900 dark:text-gray-100">MyInvois Integration (LHDN)</h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Enter your MyInvois ERP credentials to enable direct invoice submission.</p>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="myinvois_client_id" value="Client ID" />
                                            <TextInput
                                                id="myinvois_client_id"
                                                className="mt-1 block w-full"
                                                value={data.myinvois_client_id}
                                                onChange={(e) => setData('myinvois_client_id', e.target.value)}
                                            />
                                            <InputError className="mt-2" message={errors.myinvois_client_id} />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="myinvois_client_secret" value="Client Secret" />
                                            <TextInput
                                                id="myinvois_client_secret"
                                                type="password"
                                                className="mt-1 block w-full"
                                                value={data.myinvois_client_secret}
                                                onChange={(e) => setData('myinvois_client_secret', e.target.value)}
                                            />
                                            <InputError className="mt-2" message={errors.myinvois_client_secret} />
                                            <p className="mt-1 text-xs text-gray-500">Your secret is encrypted before saving.</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <PrimaryButton disabled={processing}>Save Business Profile</PrimaryButton>

                                    {recentlySuccessful && (
                                        <p className="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                                    )}
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
