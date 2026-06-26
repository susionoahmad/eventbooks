<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * Compute PPN (Value Added Tax)
     *
     * @param float $subtotal
     * @param float $rate (default 11%)
     * @return array
     */
    public function calculatePPN(float $subtotal, float $rate = 11.00): array
    {
        $ppnNominal = ($subtotal * $rate) / 100;
        return [
            'dpp' => $subtotal,
            'tarif' => $rate,
            'nominal_pajak' => $ppnNominal,
            'total' => $subtotal + $ppnNominal
        ];
    }

    /**
     * Compute PPh 23 (Withholding Tax for Services/Equipment Rental)
     * Standard: 2% of DPP (if NPWP exists), 4% of DPP (if no NPWP)
     *
     * @param float $dpp (Dasar Pengenaan Pajak)
     * @param bool $hasNpwp
     * @return array
     */
    public function calculatePPh23(float $dpp, bool $hasNpwp = true): array
    {
        $tarif = $hasNpwp ? 2.00 : 4.00;
        $nominalPajak = ($dpp * $tarif) / 100;
        $netPayout = $dpp - $nominalPajak;

        return [
            'tipe_pajak' => 'pph_23',
            'dpp' => $dpp,
            'tarif' => $tarif,
            'nominal_pajak' => $nominalPajak,
            'net_payout' => $netPayout
        ];
    }

    /**
     * Compute PPh 21 for Freelancer / Talent / MC (Non-continuous services)
     * Standard calculation: 50% * Gross Payout * Progressive rate (5% if NPWP exists, 6% if no NPWP)
     * Effectively: 2.5% of Gross Payout (with NPWP) or 3% (without NPWP)
     *
     * @param float $grossAmount
     * @param bool $hasNpwp
     * @return array
     */
    public function calculatePPh21Freelancer(float $grossAmount, bool $hasNpwp = true): array
    {
        $dpp = $grossAmount * 0.50; // 50% of gross
        $tarif = $hasNpwp ? 5.00 : 6.00; // 5% or 6% (20% higher than 5% if no NPWP)
        
        $nominalPajak = ($dpp * $tarif) / 100;
        $netPayout = $grossAmount - $nominalPajak;

        return [
            'tipe_pajak' => 'pph_21',
            'dpp' => $dpp,
            'tarif' => $tarif,
            'nominal_pajak' => $nominalPajak,
            'net_payout' => $netPayout
        ];
    }
}
