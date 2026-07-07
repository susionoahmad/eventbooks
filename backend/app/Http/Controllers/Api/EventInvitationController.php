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
                ]
            ]);
        }

        $data = $invitation->toArray();
        if ($invitation->template_background) {
            $data['template_background_url'] = asset('storage/' . $invitation->template_background);
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
        ]);

        $invitation = $event->invitation ?: new EventInvitation();
        $invitation->event_id = $event->id;
        $invitation->tenant_id = Auth::user()->tenant_id;

        $invitation->title = $request->input('title') ?: 'Undangan Event: ' . $event->nama_event;
        $invitation->date_time_info = $request->input('date_time_info') ?: '';
        $invitation->maps_url = $request->input('maps_url') ?: '';
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
                }

                $file = $request->file('background_image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('invitations/' . $event->id, $filename, 'public');
                $invitation->template_background = $path;

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
                $invitation->template_background = null;
            }

            // Fill colors from preset, or allow manual overrides
            $invitation->background_color = $request->input('background_color') ?: $preset['background_color'];
            $invitation->primary_color = $request->input('primary_color') ?: $preset['primary_color'];
            $invitation->accent_color = $request->input('accent_color') ?: $preset['accent_color'];
            $invitation->text_color = $request->input('text_color') ?: $preset['text_color'];
            $invitation->button_text_color = $request->input('button_text_color') ?: $preset['button_text_color'];
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
        if ($invitation->template_background) {
            $data['template_background_url'] = asset('storage/' . $invitation->template_background);
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
                ]
            ]);
        }

        $invData = $invitation->toArray();
        if ($invitation->template_background) {
            $invData['template_background_url'] = asset('storage/' . $invitation->template_background);
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
}
