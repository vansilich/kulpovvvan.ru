<?php

namespace App\Helpers\Api;

use Exception;
use Illuminate\Support\Facades\DB;

class OneC
{

    /**
     * Fetching all calls data by provided interval from 1C upload table 'PhoneOrder'.
     * Appending '7', because they are not in internation format
     *
     * @throws Exception
     */
    public function phoneOrders( string $startDate, string $endDate ): array
    {
        $phoneOrders = DB::connection('gb_testfl')
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
        $invoices = DB::connection('gb_testfl')
            ->table('pdf_uploads')
            ->whereIn('order_id', $invoicesIds)
            ->get();

        //setup headers
        $result = [ ['generated', 'order_amount', 'paid_amount', 'is_paid', 'paid_date'] ];
        foreach ($invoices->getIterator() as $invoice) {

            $invoice_body = [];
            $invoice_body['order_id'] = $invoice->order_id;
            $invoice_body['generated'] = $invoice->generated;
            $invoice_body['order_amount'] = $invoice->order_amount;
            $invoice_body['paid_amount'] = $invoice->paid_amount;
            //invoice is fully paid
            $invoice_body['is_paid'] =
                $invoice->order_amount !== ''
                && $invoice->order_amount === $invoice->paid_amount;
            //save date, if fully paid
            $invoice_body['paid_date'] = $invoice_body['is_paid']
                ? $invoice->paid_date
                : false;

            $result[] = $invoice_body;
        }

        return $result;
    }

}
