<?php

namespace App\Services\MyInvois;

use App\Models\Invoice;
use App\Models\MyInvoisProfile;

class UBLInvoiceTransformer
{
    public function transform(Invoice $invoice, MyInvoisProfile $profile): array
    {
        $invoice->load(["customer", "lines", "lines.account"]);
        $company = $invoice->company;

        return [
            "_D"  => "urn:oasis:names:specification:ubl:schema:xsd:Invoice-2",
            "_A"  => "urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2",
            "_B"  => "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2",
            "Invoice" => [[
                "ID"                    => [["$" => $invoice->invoice_no]],
                "IssueDate"             => [["$" => $invoice->date->format("Y-m-d")]],
                "IssueTime"             => [["$" => $invoice->date->format("H:i:s")]],
                "InvoiceTypeCode"       => [["$" => "01", "listVersionID" => "1.0"]],
                "DocumentCurrencyCode"  => [["$" => $invoice->currency_code ?? "MYR"]],
                "AccountingSupplierParty" => [[
                    "Party" => [[
                        "PartyName"           => [["Name" => [["$" => $company->name]]]],
                        "PartyTaxScheme"      => [[
                            "CompanyID" => [["$" => $profile->tin]],
                            "TaxScheme" => [["ID" => [["$" => "OAT"]]]],
                        ]],
                        "PostalAddress" => [[
                            "CityName"    => [["$" => $company->city ?? ""]],
                            "CountrySubentityCode" => [["$" => $company->state ?? ""]],
                            "Country"    => [["IdentificationCode" => [["$" => "MYS"]]]],
                        ]],
                    ]],
                ]],
                "AccountingCustomerParty" => [[
                    "Party" => [[
                        "PartyName"      => [["Name" => [["$" => $invoice->customer->name]]]],
                        "PartyTaxScheme" => [[
                            "CompanyID" => [["$" => $invoice->customer->tax_id ?? "EI00000000010"]],
                            "TaxScheme" => [["ID" => [["$" => "OAT"]]]],
                        ]],
                        "PostalAddress" => [[
                            "CityName" => [["$" => $invoice->customer->city ?? ""]],
                            "Country"  => [["IdentificationCode" => [["$" => "MYS"]]]],
                        ]],
                    ]],
                ]],
                "InvoiceLine" => $this->transformLines($invoice),
                "TaxTotal"    => [[
                    "TaxAmount"    => [["$" => number_format($invoice->tax_amount, 2), "currencyID" => "MYR"]],
                    "TaxSubtotal" => [[
                        "TaxableAmount" => [["$" => number_format($invoice->subtotal, 2), "currencyID" => "MYR"]],
                        "TaxAmount"     => [["$" => number_format($invoice->tax_amount, 2), "currencyID" => "MYR"]],
                        "TaxCategory"   => [[
                            "ID"      => [["$" => "01"]],
                            "Percent" => [["$" => "0"]],
                            "TaxScheme" => [["ID" => [["$" => "OAT"]]]],
                        ]],
                    ]],
                ]],
                "LegalMonetaryTotal" => [[
                    "LineExtensionAmount" => [["$" => number_format($invoice->subtotal, 2), "currencyID" => "MYR"]],
                    "TaxExclusiveAmount"  => [["$" => number_format($invoice->subtotal, 2), "currencyID" => "MYR"]],
                    "TaxInclusiveAmount"  => [["$" => number_format($invoice->total, 2), "currencyID" => "MYR"]],
                    "PayableAmount"       => [["$" => number_format($invoice->total, 2), "currencyID" => "MYR"]],
                ]],
            ]],
        ];
    }

    private function transformLines(Invoice $invoice): array
    {
        return $invoice->lines->map(function ($line, $index) {
            return [
                "ID"                  => [["$" => (string)($index + 1)]],
                "InvoicedQuantity"    => [["$" => number_format($line->quantity, 2), "unitCode" => "C62"]],
                "LineExtensionAmount" => [["$" => number_format($line->amount, 2), "currencyID" => "MYR"]],
                "Item" => [[
                    "Description"         => [["$" => $line->description ?? "-"]],
                    "ClassifiedTaxCategory" => [[
                        "ID"        => [["$" => "01"]],
                        "Percent"   => [["$" => "0"]],
                        "TaxScheme" => [["ID" => [["$" => "OAT"]]]],
                    ]],
                ]],
                "Price" => [[
                    "PriceAmount" => [["$" => number_format($line->unit_price, 2), "currencyID" => "MYR"]],
                ]],
                "TaxTotal" => [[
                    "TaxAmount"   => [["$" => number_format($line->tax_amount ?? 0, 2), "currencyID" => "MYR"]],
                    "TaxSubtotal" => [[
                        "TaxableAmount" => [["$" => number_format($line->amount, 2), "currencyID" => "MYR"]],
                        "TaxAmount"     => [["$" => number_format($line->tax_amount ?? 0, 2), "currencyID" => "MYR"]],
                        "TaxCategory"   => [[
                            "ID"        => [["$" => "01"]],
                            "Percent"   => [["$" => "0"]],
                            "TaxScheme" => [["ID" => [["$" => "OAT"]]]],
                        ]],
                    ]],
                ]],
            ];
        })->toArray();
    }
}
