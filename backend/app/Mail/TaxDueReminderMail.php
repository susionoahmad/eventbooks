<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaxDueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $dueTaxes;

    public function __construct($tenant, $dueTaxes)
    {
        $this->tenant = $tenant;
        $this->dueTaxes = $dueTaxes;
    }

    public function build()
    {
        return $this->subject('Pemberitahuan Jatuh Tempo Pajak - EventBooks')
                    ->html($this->getHtmlContent());
    }

    private function getHtmlContent()
    {
        $rows = '';
        foreach ($this->dueTaxes as $tax) {
            $label = match ($tax->tipe_pajak) {
                'ppn_keluaran' => 'PPN Keluaran',
                'ppn_masukan' => 'PPN Masukan',
                'pph_21' => 'PPh 21',
                'pph_23' => 'PPh 23',
                default => 'Pajak'
            };
            $ref = $tax->nomor_bukti_potong ?: ($tax->nomor_faktur_pajak ?: '-');
            $nominal = 'Rp ' . number_format($tax->nominal_pajak, 0, ',', '.');
            $deadline = $tax->deadline_date ? date('d F Y', strtotime($tax->deadline_date)) : '-';

            $rows .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$label}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$tax->masa_pajak}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd;'>{$ref}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;'>{$nominal}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #ddd; color: #d9534f; font-weight: bold;'>{$deadline}</td>
                </tr>
            ";
        }

        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                <h2 style='color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px;'>Pemberitahuan Jatuh Tempo Pajak</h2>
                <p>Halo <strong>{$this->tenant->name}</strong>,</p>
                <p>Kami mendeteksi terdapat beberapa kewajiban perpajakan Anda yang <strong>belum disetor/dilaporkan</strong> dan mendekati tanggal jatuh tempo:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                    <thead>
                        <tr style='background-color: #f9f9f9;'>
                            <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Jenis Pajak</th>
                            <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Masa Pajak</th>
                            <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>No. Ref</th>
                            <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Nominal</th>
                            <th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Tenggat Setor</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>

                <p style='margin-top: 30px;'>Mohon segera lakukan penyelesaian pembayaran dan ubah status perpajakan Anda di portal <strong>EventBooks</strong> untuk menyelaraskan pembukuan kas Anda secara presisi.</p>
                
                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 11px; color: #777;'>
                    <p>Pesan ini dikirimkan secara otomatis oleh sistem notifikasi perpajakan EventBooks.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
