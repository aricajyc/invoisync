export interface BusinessProfile {
    id?: number;
    company_name: string;
    registration_number: string;
    tax_identification_number?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone: string;
    website?: string;
    logo_url?: string;
    created_at?: string;
    updated_at?: string;
}

export interface User {
    id: number;
    full_name: string;
    email: string;
    status: string;
    user_type: 'B2C' | 'B2B' | 'Admin';
    email_verified_at?: string | null;
    business_profile?: BusinessProfile | null;
    created_at: string;
    updated_at: string;
}
