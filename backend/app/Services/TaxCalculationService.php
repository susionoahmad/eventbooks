<?php

namespace App\Services;

class TaxCalculationService
{
    /**
    /**
     * Get PPN Rate dynamically based on transaction/invoice date
     *
     * @param string $date
     * @return float
     */
    public function getPPNRateForDate(string $date): float
    {
        $timestamp = strtotime($date);
        if ($timestamp < strtotime('2022-04-01')) {
            return 10.00;
        } elseif ($timestamp < strtotime('2025-01-01')) {
            return 11.00;
        } else {
            // Dynamic custom rate from tenant settings for 2025 onwards
            if (auth()->check() && auth()->user()->tenant && auth()->user()->tenant->default_ppn_rate !== null) {
                return (float) auth()->user()->tenant->default_ppn_rate;
            }
            return 12.00; // Standard PPN since 2025 (UU HPP)
        }
    }

    /**
     * Compute PPN (Value Added Tax)
     *
     * @param float $subtotal
     * @param float|null $rate
     * @param string|null $date
     * @return array
     */
    public function calculatePPN(float $subtotal, ?float $rate = null, ?string $date = null): array
    {
        $actualRate = $rate ?? ($date ? $this->getPPNRateForDate($date) : 12.00);
        $ppnNominal = ($subtotal * $actualRate) / 100;
        return [
            'dpp' => $subtotal,
            'tarif' => $actualRate,
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
     * Standard progressive rate: 50% * Gross Payout * Progressive rate (Pasal 17)
     * Surcharge of 20% (120% of standard rate) if no NPWP.
     *
     * @param float $grossAmount
     * @param bool $hasNpwp
     * @return array
     */
    public function calculatePPh21Freelancer(float $grossAmount, bool $hasNpwp = true): array
    {
        $dpp = $grossAmount * 0.50; // 50% of gross
        
        $sisaDpp = $dpp;
        $nominalPajak = 0.00;
        
        // Progressive Brackets under UU HPP
        $brackets = [
            ['limit' => 60000000.00, 'rate' => 0.05],
            ['limit' => 190000000.00, 'rate' => 0.15],  // 250m - 60m
            ['limit' => 250000000.00, 'rate' => 0.25],  // 500m - 250m
            ['limit' => 4500000000.00, 'rate' => 0.30], // 5b - 500m
            ['limit' => PHP_FLOAT_MAX, 'rate' => 0.35]
        ];
        
        foreach ($brackets as $bracket) {
            if ($sisaDpp <= 0) {
                break;
            }
            $taxable = min($sisaDpp, $bracket['limit']);
            $nominalPajak += $taxable * $bracket['rate'];
            $sisaDpp -= $taxable;
        }
        
        if (!$hasNpwp) {
            $nominalPajak *= 1.20; // 20% penalty/surcharge if no NPWP
        }
        
        $netPayout = $grossAmount - $nominalPajak;
        
        // Approximate average rate for UI reference
        $effectiveRate = $grossAmount > 0 ? ($nominalPajak / $dpp) * 100 : ($hasNpwp ? 5.00 : 6.00);

        return [
            'tipe_pajak' => 'pph_21',
            'dpp' => $dpp,
            'tarif' => round($effectiveRate, 2),
            'nominal_pajak' => $nominalPajak,
            'net_payout' => $netPayout
        ];
    }
}
