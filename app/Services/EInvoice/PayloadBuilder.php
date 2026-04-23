<?php

namespace App\Services\EInvoice;

use App\Models\Invoice;

class PayloadBuilder
{
    public function build(Invoice $invoice): array
    {
        $invoice->load(['customer', 'items']);

        return [
            '_D' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
            '_A' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
            '_B' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
            'Invoice' => [
                $this->buildInvoiceBody($invoice),
            ],
        ];
    }

    private function buildInvoiceBody(Invoice $invoice): array
    {
        return [
            'ID'                   => [['_' => $invoice->invoice_no]],
            'IssueDate'            => [['_' => $invoice->invoice_date->format('Y-m-d')]],
            'IssueTime'            => [['_' => $invoice->invoice_date->format('H:i:s') . 'Z']],
            'InvoiceTypeCode'      => [['_' => '01', 'listVersionID' => '1.0']],
            'DocumentCurrencyCode' => [['_' => 'MYR']],
            'TaxCurrencyCode'      => [['_' => 'MYR']],

            'AccountingSupplierParty' => [$this->buildSupplier()],
            'AccountingCustomerParty' => [$this->buildBuyer($invoice)],

            'PaymentMeans' => [
                [
                    'PaymentMeansCode'      => [['_' => '03']],
                    'PayeeFinancialAccount' => [['ID' => [['_' => 'NA']]]],
                ]
            ],

            'TaxTotal'           => [$this->buildTaxTotal($invoice)],
            'LegalMonetaryTotal' => [$this->buildMonetaryTotal($invoice)],
            'InvoiceLine'        => $this->buildInvoiceLines($invoice),
        ];
    }

    private function buildSupplier(): array
    {
        $supplier = config('einvoice.supplier');

        return [
            'Party' => [
                [
                    'IndustryClassificationCode' => [
                        [
                            '_'    => $supplier['msic'],
                            'name' => $supplier['msic_desc'],
                        ]
                    ],
                    'PartyIdentification' => [
                        ['ID' => [['_' => $supplier['tin'],  'schemeID' => 'TIN']]],
                        ['ID' => [['_' => $supplier['brn'],  'schemeID' => 'BRN']]],
                        ['ID' => [['_' => $supplier['sst'],  'schemeID' => 'SST']]],
                        ['ID' => [['_' => 'NA',              'schemeID' => 'TTX']]],
                    ],
                    'PostalAddress'   => [$this->buildAddress($supplier['address'])],
                    'PartyLegalEntity' => [
                        ['RegistrationName' => [['_' => $supplier['name']]]]
                    ],
                    'Contact' => [
                        [
                            'Telephone'     => [['_' => $supplier['phone']]],
                            'ElectronicMail'=> [['_' => $supplier['email']]],
                        ]
                    ],
                ]
            ],
        ];
    }

    private function buildBuyer(Invoice $invoice): array
    {
        $customer = $invoice->customer;

        return [
            'Party' => [
                [
                    'PartyIdentification' => [
                        ['ID' => [['_' => $customer->tin    ?? 'EI00000000010', 'schemeID' => 'TIN']]],
                        ['ID' => [['_' => $customer->id_value ?? 'NA',          'schemeID' => $customer->id_type ?? 'BRN']]],
                        ['ID' => [['_' => $customer->sst_registration_no ?? 'NA', 'schemeID' => 'SST']]],
                        ['ID' => [['_' => 'NA',                                   'schemeID' => 'TTX']]],
                    ],
                    'PostalAddress'    => [$this->buildBuyerAddress($customer)],
                    'PartyLegalEntity' => [
                        ['RegistrationName' => [['_' => $customer->name]]]
                    ],
                    'Contact' => [
                        [
                            'Telephone'      => [['_' => $customer->phone ?? 'NA']],
                            'ElectronicMail' => [['_' => $customer->email ?? 'NA']],
                        ]
                    ],
                ]
            ],
        ];
    }

    private function buildAddress(array $addr): array
    {
        return [
            'CityName'            => [['_' => $addr['city']]],
            'PostalZone'          => [['_' => $addr['postcode']]],
            'CountrySubentityCode'=> [['_' => $addr['state']]],
            'AddressLine'         => [
                ['Line' => [['_' => $addr['line1']]]],
                ['Line' => [['_' => $addr['line2'] ?? '']]],
                ['Line' => [['_' => $addr['line3'] ?? '']]],
            ],
            'Country' => [
                [
                    'IdentificationCode' => [
                        [
                            '_'             => $addr['country'],
                            'listID'        => 'ISO3166-1',
                            'listAgencyID'  => '6',
                        ]
                    ]
                ]
            ],
        ];
    }

    private function buildBuyerAddress($customer): array
    {
        return [
            'CityName'            => [['_' => $customer->city     ?? 'NA']],
            'PostalZone'          => [['_' => $customer->postcode ?? '00000']],
            'CountrySubentityCode'=> [['_' => $customer->state    ?? '00']],
            'AddressLine'         => [
                ['Line' => [['_' => $customer->address ?? 'NA']]],
                ['Line' => [['_' => '']]],
                ['Line' => [['_' => '']]],
            ],
            'Country' => [
                [
                    'IdentificationCode' => [
                        [
                            '_'            => 'MYS',
                            'listID'       => 'ISO3166-1',
                            'listAgencyID' => '6',
                        ]
                    ]
                ]
            ],
        ];
    }

    private function buildTaxTotal(Invoice $invoice): array
    {
        $taxAmount = $invoice->items->sum('tax_amount') ?? 0;

        return [
            'TaxAmount' => [['_' => round($taxAmount, 2), 'currencyID' => 'MYR']],
            'TaxSubtotal' => [
                [
                    'TaxableAmount' => [['_' => round($invoice->subtotal, 2), 'currencyID' => 'MYR']],
                    'TaxAmount'     => [['_' => round($taxAmount, 2),         'currencyID' => 'MYR']],
                    'TaxCategory'   => [
                        [
                            'ID'        => [['_' => '01']],
                            'TaxScheme' => [
                                [
                                    'ID' => [
                                        [
                                            '_'             => 'OTH',
                                            'schemeID'      => 'UN/ECE 5153',
                                            'schemeAgencyID'=> '6',
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ],
                ]
            ],
        ];
    }

    private function buildMonetaryTotal(Invoice $invoice): array
    {
        return [
            'LineExtensionAmount' => [['_' => round($invoice->subtotal,    2), 'currencyID' => 'MYR']],
            'TaxExclusiveAmount'  => [['_' => round($invoice->subtotal,    2), 'currencyID' => 'MYR']],
            'TaxInclusiveAmount'  => [['_' => round($invoice->total_amount,2), 'currencyID' => 'MYR']],
            'PayableAmount'       => [['_' => round($invoice->total_amount,2), 'currencyID' => 'MYR']],
        ];
    }

    private function buildInvoiceLines(Invoice $invoice): array
    {
        return $invoice->items->map(function ($item, $index) {
            return [
                'ID'               => [['_' => (string)($index + 1)]],
                'InvoicedQuantity' => [['_' => $item->quantity, 'unitCode' => 'C62']],
                'LineExtensionAmount' => [
                    ['_' => round($item->quantity * $item->unit_price, 2), 'currencyID' => 'MYR']
                ],
                'TaxTotal' => [
                    [
                        'TaxAmount'  => [['_' => round($item->tax_amount ?? 0, 2), 'currencyID' => 'MYR']],
                        'TaxSubtotal'=> [
                            [
                                'TaxableAmount' => [['_' => round($item->quantity * $item->unit_price, 2), 'currencyID' => 'MYR']],
                                'TaxAmount'     => [['_' => round($item->tax_amount ?? 0, 2),             'currencyID' => 'MYR']],
                                'Percent'       => [['_' => $item->tax_rate ?? 0]],
                                'TaxCategory'   => [
                                    [
                                        'ID'        => [['_' => '01']],
                                        'TaxScheme' => [
                                            [
                                                'ID' => [
                                                    [
                                                        '_'             => 'OTH',
                                                        'schemeID'      => 'UN/ECE 5153',
                                                        'schemeAgencyID'=> '6',
                                                    ]
                                                ]
                                            ]
                                        ],
                                    ]
                                ],
                            ]
                        ],
                    ]
                ],
                'Item' => [
                    [
                        'CommodityClassification' => [
                            [
                                'ItemClassificationCode' => [
                                    ['_' => '022', 'listID' => 'CLASS']
                                ]
                            ]
                        ],
                        'Description' => [['_' => $item->description ?? 'Service']],
                    ]
                ],
                'Price' => [
                    ['PriceAmount' => [['_' => round($item->unit_price, 2), 'currencyID' => 'MYR']]]
                ],
            ];
        })->toArray();
    }
}