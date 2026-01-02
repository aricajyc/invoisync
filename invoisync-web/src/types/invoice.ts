export interface Invoice {
  id: number;
  invoice_number: string;
  invoice_type: '01' | '02' | '03' | '04';
  invoice_type_name: string;
  invoice_date_time: string;
  
  supplier: {
    name: string;
    tin: string;
    email: string;
    msic_code: string;
    business_activity: string;
    address: string;
    contact_number: string;
  };
  
  buyer: {
    name: string;
    tin: string;
    email?: string;
    address: string;
    contact_number?: string;
  };
  
  financials: {
    currency_code: string;
    total_excluding_tax: string;
    total_tax_amount: string;
    total_including_tax: string;
    total_payable_amount: string;
  };
  
  line_items: LineItem[];
  tax_summaries: TaxSummary[];
  
  status: 'draft' | 'validated' | 'submitted' | 'approved' | 'rejected' | 'cancelled';
  is_draft: boolean;
  is_validated: boolean;
  is_submitted: boolean;
  is_editable: boolean;
  can_submit: boolean;
  can_cancel: boolean;
  
  myinvois?: {
    uid?: string;
    qr_code_data?: string;
    validation_date_time?: string;
  };
  
  created_at: string;
  updated_at: string;
}

export interface LineItem {
  id: number;
  line_number: number;
  classification_code: string;
  product_service_description: string;
  quantity: string;
  unit_of_measure: string;
  unit_price: string;
  subtotal: string;
  tax: {
    type: string;
    type_name: string;
    rate: number;
    amount: string;
  };
  totals: {
    excluding_tax: string;
    including_tax: string;
  };
}

export interface TaxSummary {
  id: number;
  tax_type: string;
  tax_type_name: string;
  taxable_amount: string;
  tax_rate: number;
  tax_amount: string;
}