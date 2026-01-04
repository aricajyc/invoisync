import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import SimpleCombobox from '@/Components/SimpleCombobox';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

export default function UpdateBusinessProfileInformation({
    className = '',
}) {
    // Get profile from props provided by ProfileController
    const profile = usePage().props.profile;
    const user = usePage().props.auth.user;

    const { data, setData, post, errors, processing, recentlySuccessful } = useForm({
        business_name: profile?.business_name || '',
        business_registration_number: profile?.business_registration_number || '',
        tax_identification_number: profile?.tax_identification_number || '',
        sst_registration_number: profile?.sst_registration_number || '',
        tourism_tax_registration_number: profile?.tourism_tax_registration_number || '',
        msic_code: profile?.msic_code || '',
        business_activity_description: profile?.business_activity_description || '',
        contact_phone: profile?.contact_phone || '',
        contact_email: profile?.contact_email || user.email,
        address_line_0: profile?.address_line_0 || '',
        address_line_1: profile?.address_line_1 || '',
        address_line_2: profile?.address_line_2 || '',
        city: profile?.city || '',
        state: profile?.state || '',
        postal_zone: profile?.postal_zone || '',
        country: profile?.country || 'MYS',
    });

    const [countries, setCountries] = useState([]);
    const [states, setStates] = useState([]);
    const [msicCodes, setMsicCodes] = useState([]);
    const [loadingCodes, setLoadingCodes] = useState(true);

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
                setMsicCodes(Array.isArray(mRes.data) ? mRes.data : Object.values(mRes.data));
            } catch (error) {
                console.error("Failed to load reference data", error);
            } finally {
                setLoadingCodes(false);
            }
        };
        fetchCodes();
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('business-profile.store'), {
            preserveScroll: true,
        });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Business Profile Information
                </h2>

                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Update your business details, address, and contact information.
                </p>
            </header>

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
                        <TextInput
                            id="tax_identification_number"
                            className="mt-1 block w-full"
                            value={data.tax_identification_number}
                            onChange={(e) => setData('tax_identification_number', e.target.value)}
                            required
                            placeholder="C1234567890"
                        />
                        <InputError className="mt-2" message={errors.tax_identification_number} />
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
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Saved.
                        </p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
