<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Services\ColorExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class EventInvitationController extends Controller
{
    // Presets definition
    protected array $presets = [
        'classic' => [
            'background_color' => '#ffffff',
            'primary_color' => '#1a1a1a',
            'accent_color' => '#4f46e5',
            'text_color' => '#1a1a1a',
            'button_text_color' => '#ffffff',
            'font_family' => 'Inter'
        ],
        'elegant' => [
            'background_color' => '#111827',
            'primary_color' => '#f9fafb',
            'accent_color' => '#d4af37',
            'text_color' => '#f9fafb',
            'button_text_color' => '#111827',
            'font_family' => 'Playfair Display'
        ],
        'floral' => [
            'background_color' => '#fdf2f8',
            'primary_color' => '#831843',
            'accent_color' => '#db2777',
            'text_color' => '#831843',
            'button_text_color' => '#ffffff',
            'font_family' => 'Sacramento'
        ],
        'modern' => [
            'background_color' => '#f3f4f6',
            'primary_color' => '#111827',
            'accent_color' => '#06b6d4',
            'text_color' => '#111827',
            'button_text_color' => '#ffffff',
            'font_family' => 'Montserrat'
        ],
        'sunset' => [
            'background_color' => '#fff7ed',
            'primary_color' => '#7c2d12',
            'accent_color' => '#ea580c',
            'text_color' => '#7c2d12',
            'button_text_color' => '#ffffff',
            'font_family' => 'Lora'
        ],
        'rustic_elegance' => [
            'background_color' => '#fafaf9',
            'primary_color' => '#44403c',
            'accent_color' => '#78716c',
            'text_color' => '#44403c',
            'button_text_color' => '#ffffff',
            'font_family' => 'Playfair Display'
        ],
        'cyber_neon' => [
            'background_color' => '#030712',
            'primary_color' => '#f3f4f6',
            'accent_color' => '#ec4899',
            'text_color' => '#f3f4f6',
            'button_text_color' => '#ffffff',
            'font_family' => 'Montserrat'
        ],
        'royal_corporate' => [
            'background_color' => '#0f172a',
            'primary_color' => '#f8fafc',
            'accent_color' => '#2563eb',
            'text_color' => '#f8fafc',
            'button_text_color' => '#ffffff',
            'font_family' => 'Cinzel'
        ],
        'warm_leafy' => [
            'background_color' => '#f0fdf4',
            'primary_color' => '#14532d',
            'accent_color' => '#16a34a',
            'text_color' => '#14532d',
            'button_text_color' => '#ffffff',
            'font_family' => 'Lora'
        ]
    ];

    /**
     * Show the invitation settings for an event.
     */
    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $invitation = $event->invitation;

        if (!$invitation) {
            // Return default template data
            return response()->json([
                'data' => [
                    'event_id' => $event->id,
                    'title' => 'Undangan Event: ' . $event->nama_event,
                    'date_time_info' => $event->tanggal_mulai->format('d M Y') . ($event->tanggal_selesai && $event->tanggal_selesai != $event->tanggal_mulai ? ' s/d ' . $event->tanggal_selesai->format('d M Y') : ''),
                    'maps_url' => '',
                    'is_custom_template' => false,
                    'preset_template' => 'classic',
                    'template_background' => null,
                    'background_color' => '#ffffff',
                    'primary_color' => '#1a1a1a',
                    'accent_color' => '#4f46e5',
                    'text_color' => '#1a1a1a',
                    'button_text_color' => '#ffffff',
                    'font_family' => 'Inter',
                    'maps_btn_top' => 72.00,
                    'maps_btn_left' => 15.00,
                    'maps_btn_width' => 70.00,
                    'maps_btn_text' => 'Buka Google Maps',
                    'maps_btn_height' => 6.00,
                ]
            ]);
        }

        $data = $invitation->toArray();
        if (isset($data['maps_url']) && ($data['maps_url'] === 'null' || $data['maps_url'] === 'undefined')) {
            $data['maps_url'] = '';
        }
        if ($invitation->template_background) {
            $data['template_background_url'] = request()->schemeAndHttpHost() . '/api/v1/events/' . $event->id . '/invitation/background';
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Save/update invitation settings for an event.
     */
    public function save(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'date_time_info' => 'nullable|string',
            'maps_url' => 'nullable|string',
            'is_custom_template' => 'required|boolean',
            'preset_template' => 'required|string',
            'font_family' => 'required|string',
            'background_image' => 'nullable|image|max:5120', // Max 5MB
            // User can override manually
            'background_color' => 'nullable|string|size:7',
            'primary_color' => 'nullable|string|size:7',
            'accent_color' => 'nullable|string|size:7',
            'text_color' => 'nullable|string|size:7',
            'button_text_color' => 'nullable|string|size:7',
            // Coordinates validation
            'maps_btn_top' => 'nullable|numeric|between:0,100',
            'maps_btn_left' => 'nullable|numeric|between:0,100',
            'maps_btn_width' => 'nullable|numeric|between:0,100',
            'maps_btn_height' => 'nullable|numeric|between:0,100',
            'maps_btn_text' => 'nullable|string|max:100',
        ]);

        $invitation = $event->invitation ?: new EventInvitation();
        $invitation->event_id = $event->id;
        $invitation->tenant_id = Auth::user()->tenant_id;

        $invitation->title = $request->input('title') ?: 'Undangan Event: ' . $event->nama_event;
        $invitation->date_time_info = $request->input('date_time_info') ?: '';
        
        $mapsUrl = $request->input('maps_url');
        if ($mapsUrl === 'null' || $mapsUrl === 'undefined') {
            $mapsUrl = '';
        }
        $invitation->maps_url = $mapsUrl ?: '';
        $invitation->font_family = $request->input('font_family') ?: 'Inter';

        $isCustom = $request->boolean('is_custom_template');
        $invitation->is_custom_template = $isCustom;
        $presetName = $request->input('preset_template');
        $invitation->preset_template = $presetName;

        // Apply defaults from preset
        $preset = $this->presets[$presetName] ?? $this->presets['classic'];

        if ($isCustom) {
            // Processing uploaded background image
            if ($request->hasFile('background_image')) {
                // Delete old file if exists
                if ($invitation->template_background && Storage::disk('public')->exists($invitation->template_background)) {
                    Storage::disk('public')->delete($invitation->template_background);
                    
                    // Delete old thumbnail if exists
                    $oldThumb = 'invitations/' . $event->id . '/' . pathinfo($invitation->template_background, PATHINFO_FILENAME) . '_thumb.jpg';
                    if (Storage::disk('public')->exists($oldThumb)) {
                        Storage::disk('public')->delete($oldThumb);
                    }
                }

                $file = $request->file('background_image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('invitations/' . $event->id, $filename, 'public');
                $invitation->template_background = $path;

                // Generate optimized landscape thumbnail for WhatsApp preview (1.91:1 ratio, terkompresi)
                $thumbnailPath = 'invitations/' . $event->id . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_thumb.jpg';
                \App\Services\ThumbnailGenerator::generate(
                    storage_path('app/public/' . $path),
                    storage_path('app/public/' . $thumbnailPath)
                );

                // Color palette extraction using GD
                $absolutePath = storage_path('app/public/' . $path);
                $palette = ColorExtractor::extract($absolutePath, 5);
                
                // Set theme colors based on extracted palette
                $bgColor = $palette[0] ?? '#ffffff';
                $accentColor = $palette[1] ?? '#4f46e5';
                $primaryColor = $palette[2] ?? '#1a1a1a';

                $invitation->background_color = $bgColor;
                $invitation->accent_color = $accentColor;
                $invitation->primary_color = $primaryColor;

                // Anti Teks Mati: dynamic text contrasting
                $invitation->text_color = ColorExtractor::isDark($bgColor) ? '#ffffff' : '#1a1a1a';
                $invitation->button_text_color = ColorExtractor::isDark($accentColor) ? '#ffffff' : '#1a1a1a';

                // OCR Processing for Address Bounding Box / Google Maps link
                $ocrData = \App\Services\OcrService::extractAddressCoordinates($absolutePath);
                $invitation->maps_btn_top = $ocrData['maps_btn_top'];
                $invitation->maps_btn_left = $ocrData['maps_btn_left'];
                $invitation->maps_btn_width = $ocrData['maps_btn_width'];
                if ($ocrData['maps_url'] && !$invitation->maps_url) {
                    $invitation->maps_url = $ocrData['maps_url'];
                }
            } else {
                // If custom is toggled but no new file is uploaded, keep old image settings or apply values
                $invitation->background_color = $request->input('background_color') ?: $invitation->background_color ?: '#ffffff';
                $invitation->primary_color = $request->input('primary_color') ?: $invitation->primary_color ?: '#1a1a1a';
                $invitation->accent_color = $request->input('accent_color') ?: $invitation->accent_color ?: '#4f46e5';
                $invitation->text_color = ColorExtractor::isDark($invitation->background_color) ? '#ffffff' : '#1a1a1a';
                $invitation->button_text_color = ColorExtractor::isDark($invitation->accent_color) ? '#ffffff' : '#1a1a1a';
            }
        } else {
            // Apply Preset colors. Delete custom background if toggled off
            if ($invitation->template_background && Storage::disk('public')->exists($invitation->template_background)) {
                Storage::disk('public')->delete($invitation->template_background);
                
                // Delete thumbnail if exists
                $oldThumb = 'invitations/' . $event->id . '/' . pathinfo($invitation->template_background, PATHINFO_FILENAME) . '_thumb.jpg';
                if (Storage::disk('public')->exists($oldThumb)) {
                    Storage::disk('public')->delete($oldThumb);
                }
                
                $invitation->template_background = null;
            }

            // Fill colors from preset, or allow manual overrides
            $invitation->background_color = $request->input('background_color') ?: $preset['background_color'];
            $invitation->primary_color = $request->input('primary_color') ?: $preset['primary_color'];
            $invitation->accent_color = $request->input('accent_color') ?: $preset['accent_color'];
            $invitation->text_color = $request->input('text_color') ?: $preset['text_color'];
            $invitation->button_text_color = $request->input('button_text_color') ?: $preset['button_text_color'];
        }

        // Allow manual coordinates overrides
        if ($request->has('maps_btn_top')) {
            $invitation->maps_btn_top = $request->input('maps_btn_top') !== null && $request->input('maps_btn_top') !== 'null' ? (float)$request->input('maps_btn_top') : null;
        }
        if ($request->has('maps_btn_left')) {
            $invitation->maps_btn_left = $request->input('maps_btn_left') !== null && $request->input('maps_btn_left') !== 'null' ? (float)$request->input('maps_btn_left') : null;
        }
        if ($request->has('maps_btn_width')) {
            $invitation->maps_btn_width = $request->input('maps_btn_width') !== null && $request->input('maps_btn_width') !== 'null' ? (float)$request->input('maps_btn_width') : null;
        }
        if ($request->has('maps_btn_height')) {
            $invitation->maps_btn_height = $request->input('maps_btn_height') !== null && $request->input('maps_btn_height') !== 'null' ? (float)$request->input('maps_btn_height') : null;
        }
        if ($request->has('maps_btn_text')) {
            $invitation->maps_btn_text = $request->input('maps_btn_text') ?: 'Buka Google Maps';
        }

        // Allow manual color code override if user specifically requested (gives maximum freedom)
        if ($request->has('background_color') && $request->input('background_color')) {
            $invitation->background_color = $request->input('background_color');
            $invitation->text_color = ColorExtractor::isDark($invitation->background_color) ? '#ffffff' : '#1a1a1a';
        }
        if ($request->has('accent_color') && $request->input('accent_color')) {
            $invitation->accent_color = $request->input('accent_color');
            $invitation->button_text_color = ColorExtractor::isDark($invitation->accent_color) ? '#ffffff' : '#1a1a1a';
        }
        if ($request->has('primary_color') && $request->input('primary_color')) {
            $invitation->primary_color = $request->input('primary_color');
        }
        if ($request->has('text_color') && $request->input('text_color')) {
            $invitation->text_color = $request->input('text_color');
        }
        if ($request->has('button_text_color') && $request->input('button_text_color')) {
            $invitation->button_text_color = $request->input('button_text_color');
        }

        $invitation->save();

        // Audit Log entry
        \App\Models\AuditLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'activity' => 'Update Invitation',
            'description' => "User " . Auth::user()->name . " memperbarui desain undangan untuk event \"{$event->nama_event}\" (Preset: " . ($isCustom ? 'Kustom' : $presetName) . ")",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $data = $invitation->toArray();
        if (isset($data['maps_url']) && ($data['maps_url'] === 'null' || $data['maps_url'] === 'undefined')) {
            $data['maps_url'] = '';
        }
        if ($invitation->template_background) {
            $data['template_background_url'] = $request->schemeAndHttpHost() . '/api/v1/events/' . $event->id . '/invitation/background';
        }

        return response()->json([
            'message' => 'Desain undangan berhasil disimpan',
            'data' => $data
        ]);
    }

    /**
     * Show public page invitation details (unauthenticated guest access).
     */
    public function showPublic(Request $request, $id): JsonResponse
    {
        // Load event without TenantScope so public users can access it
        $event = Event::withoutGlobalScopes()->findOrFail($id);

        $invitation = EventInvitation::withoutGlobalScopes()
            ->where('event_id', $event->id)
            ->first();

        // Safe client data
        $clientData = null;
        if ($event->client) {
            $clientData = [
                'nama' => $event->client->nama,
                'perusahaan' => $event->client->perusahaan,
            ];
        }

        if (!$invitation) {
            // Return defaults
            return response()->json([
                'event' => [
                    'nama_event' => $event->nama_event,
                    'lokasi' => $event->lokasi,
                    'tanggal_mulai' => $event->tanggal_mulai->format('Y-m-d'),
                    'tanggal_selesai' => $event->tanggal_selesai->format('Y-m-d'),
                    'client' => $clientData,
                ],
                'data' => [
                    'title' => 'Undangan Event: ' . $event->nama_event,
                    'date_time_info' => $event->tanggal_mulai->format('d M Y') . ($event->tanggal_selesai && $event->tanggal_selesai != $event->tanggal_mulai ? ' s/d ' . $event->tanggal_selesai->format('d M Y') : ''),
                    'maps_url' => '',
                    'is_custom_template' => false,
                    'preset_template' => 'classic',
                    'template_background' => null,
                    'background_color' => '#ffffff',
                    'primary_color' => '#1a1a1a',
                    'accent_color' => '#4f46e5',
                    'text_color' => '#1a1a1a',
                    'button_text_color' => '#ffffff',
                    'font_family' => 'Inter',
                    'maps_btn_top' => 72.00,
                    'maps_btn_left' => 15.00,
                    'maps_btn_width' => 70.00,
                    'maps_btn_text' => 'Buka Google Maps',
                    'maps_btn_height' => 6.00,
                ]
            ]);
        }

        $invData = $invitation->toArray();
        if (isset($invData['maps_url']) && ($invData['maps_url'] === 'null' || $invData['maps_url'] === 'undefined')) {
            $invData['maps_url'] = '';
        }
        if ($invitation->template_background) {
            $invData['template_background_url'] = $request->schemeAndHttpHost() . '/api/v1/events/' . $event->id . '/invitation/background';
        }

        return response()->json([
            'event' => [
                'nama_event' => $event->nama_event,
                'lokasi' => $event->lokasi,
                'tanggal_mulai' => $event->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai' => $event->tanggal_selesai->format('Y-m-d'),
                'client' => $clientData,
            ],
            'data' => $invData
        ]);
    }

    /**
     * Stream the background image file directly from storage.
     */
    public function streamBackground(Request $request, $eventId)
    {
        $event = Event::withoutGlobalScopes()->findOrFail($eventId);
        $invitation = EventInvitation::withoutGlobalScopes()->where('event_id', $event->id)->first();

        if (!$invitation || !$invitation->template_background) {
            abort(404);
        }

        $path = $invitation->template_background;

        // If thumbnail is requested and exists, serve the thumbnail instead
        if ($request->query('thumb') == 1) {
            $thumbnailPath = 'invitations/' . $event->id . '/' . pathinfo($path, PATHINFO_FILENAME) . '_thumb.jpg';
            if (Storage::disk('public')->exists($thumbnailPath)) {
                $path = $thumbnailPath;
            }
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }

    /**
     * Show public page invitation details with Open Graph tags for crawlers.
     */
    public function showPublicCrawler(Request $request, $id)
    {
        // Extract numeric ID from slug if present (e.g. "12-wedding-party" -> 12)
        $eventId = (int) explode('-', $id)[0];

        // Load event without GlobalScopes to bypass tenant restrictions for public access
        $event = Event::withoutGlobalScopes()->findOrFail($eventId);

        $invitation = EventInvitation::withoutGlobalScopes()
            ->where('event_id', $event->id)
            ->first();

        $title = $invitation && $invitation->title ? $invitation->title : 'Undangan Event: ' . $event->nama_event;
        
        $description = '';
        if ($invitation && $invitation->date_time_info) {
            $description = $invitation->date_time_info;
        } else {
            $description = $event->tanggal_mulai->format('d M Y') . ($event->tanggal_selesai && $event->tanggal_selesai != $event->tanggal_mulai ? ' s/d ' . $event->tanggal_selesai->format('d M Y') : '');
        }

        if ($event->lokasi) {
            $description .= ' | Lokasi: ' . $event->lokasi;
        }

        $imageUrl = null;
        if ($invitation && $invitation->template_background) {
            $thumbnailPath = 'invitations/' . $event->id . '/' . pathinfo($invitation->template_background, PATHINFO_FILENAME) . '_thumb.jpg';
            if (Storage::disk('public')->exists($thumbnailPath)) {
                $imageUrl = $request->schemeAndHttpHost() . '/api/v1/events/' . $event->id . '/invitation/background?thumb=1';
            } else {
                $imageUrl = $request->schemeAndHttpHost() . '/api/v1/events/' . $event->id . '/invitation/background';
            }
        } else {
            // Menggunakan gambar ilustrasi event berkualitas tinggi sebagai fallback default (dioptimalkan untuk WA)
            $imageUrl = 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=600&auto=format&fit=crop';
        }

        return view('share.invitation_crawler', compact('title', 'description', 'imageUrl'));
    }
}
