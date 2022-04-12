<?php

namespace App\Handlers\Dashboard;

use Exception;
use Illuminate\Support\Facades\DB;

class OneC
{

    public function __construct(
        private DB $DB
    ){}

    /**
     * Fetching all calls data by provided interval from 1C upload table 'PhoneOrder'.
     * Appending '7', because they are not in internation format
     *
     * @throws Exception
     */
    public function phoneOrders( string $startDate, string $endDate ): array
    {
        $phoneOrders = $this->DB::connection('gb_testfl')
            ->table('PhoneOrder')
            ->where('call_date', '>=', $startDate)
            ->where('call_date', '<=', $endDate)
            ->get();

        $phoneOrdersWithPhoneKeys = [];
        foreach ($phoneOrders->getIterator() as $phoneOrder){

            $phoneOrder->phone = '7'.$phoneOrder->phone;
            $phoneOrdersWithPhoneKeys[$phoneOrder->phone][] = $phoneOrder;
        }

        return $phoneOrdersWithPhoneKeys;
    }

    /**
     * Fetching all call data by period from 1C upload table 'PhoneOrder'
     *
     * @throws Exception
     */
    public function getInvoiceInfo( array $invoicesIds ): array
    {
        $invoices = $this->DB::connection('gb_testfl')
            ->table('pdf_uploads')
            ->whereIn('order_id', $invoicesIds)
            ->get();

        $result = [];
        foreach ($invoices->getIterator() as $invoice) {

            $invoice_body = [];
            $invoice_body['generated'] = $invoice->generated;

            //invoice is fully paid
            if ($invoice->paid_percent === '100%') {
                $invoice_body['paid_date'] = $invoice->paid_date;
            }

            $result[] = $invoice_body;
        }

        return $result;
    }

}
