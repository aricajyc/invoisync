<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceTemplateRequest;
use App\Http\Resources\InvoiceTemplateResource;
use App\Models\InvoiceTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceTemplateController extends Controller
{
    /**
     * List all templates
     * 
     * @group Invoice Templates (B2C)
     */
    public function index(Request $request): JsonResponse
    {
        $templates = InvoiceTemplate::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('usage_count', 'desc')
            ->get();

        return response()->json([
            'data' => InvoiceTemplateResource::collection($templates),
        ]);
    }

    /**
     * Store new template
     * 
     * @group Invoice Templates (B2C)
     */
    public function store(InvoiceTemplateRequest $request): JsonResponse
    {
        // If setting as default, unset other defaults
        if ($request->input('is_default', false)) {
            InvoiceTemplate::where('user_id', auth()->id())
                ->update(['is_default' => false]);
        }

        $template = auth()->user()->invoiceTemplates()->create($request->validated());

        return response()->json([
            'message' => 'Template created successfully',
            'data' => new InvoiceTemplateResource($template),
        ], 201);
    }

    /**
     * Show template
     * 
     * @group Invoice Templates (B2C)
     */
    public function show(InvoiceTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        return response()->json([
            'data' => new InvoiceTemplateResource($template),
        ]);
    }

    /**
     * Update template
     * 
     * @group Invoice Templates (B2C)
     */
    public function update(InvoiceTemplateRequest $request, InvoiceTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);

        // If setting as default, unset other defaults
        if ($request->input('is_default', false)) {
            InvoiceTemplate::where('user_id', auth()->id())
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $template->update($request->validated());

        return response()->json([
            'message' => 'Template updated successfully',
            'data' => new InvoiceTemplateResource($template),
        ]);
    }

    /**
     * Delete template
     * 
     * @group Invoice Templates (B2C)
     */
    public function destroy(InvoiceTemplate $template): JsonResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * Create invoice from template
     * 
     * @group Invoice Templates (B2C)
     */
    public function createInvoiceFromTemplate(InvoiceTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        try {
            $invoice = Invoice::create(array_merge(
                $template->template_data,
                [
                    'user_id' => auth()->id(),
                    'status' => 'draft',
                    'invoice_date_time' => now(),
                ]
            ));

            // Increment usage count
            $template->increment('usage_count');

            return response()->json([
                'message' => 'Invoice created from template',
                'data' => new InvoiceResource($invoice),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create invoice from template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}