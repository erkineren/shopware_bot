<?php

namespace ShopwareBot\Modules;
/**
 * Class OrderModule
 * @package ShopwareBot\Modules
 */
class OrderModule extends BaseModule
{

    /**
     * @param $ordernumber
     * @return mixed
     */
    public function getOrderByNumber($ordernumber)
    {
        $res = $this->getList(
            [
                [
                    'property' => 'orders.number',
                    'value' => $ordernumber,
                    'operator' => NULL,
                    'expression' => NULL,
                ]
            ]
        );

        if ($res && $res['success']) return $res['data'][0];

        return $res;
    }

    /**
     * @param $ordernumbers
     * @return bool|mixed
     */
    public function getOrdersByNumber($ordernumbers)
    {
        if (!is_array($ordernumbers))
            $ordernumbers = array_map('trim', explode(',', $ordernumbers));

        if (!$ordernumbers) return false;

        $res = $this->getList([
            [
                'property' => 'orders.number',
                'value' => $ordernumbers,
                'operator' => NULL,
                'expression' => 'IN',
            ]
        ]);

        if ($res && $res['success']) return $res['data'];

        return $res;
    }

    /**
     * @param array $filter
     * @param int $page
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getList($filter = [], $page = 1, $start = 0, $limit = 5)
    {
        $res = $this->client->get('/Order/getList',
            [
                '_dc' => $this->getTimestamp(),
                'stockBasedFilterWarehouseId' => 1,
                'page' => $page,
                'start' => $start,
                'limit' => $limit,
                'filter' => json_encode($filter)
            ]);

        return json_decode($this->wrapClassNames($res), true);
    }

    /**
     * As default,  preview mode is active
     *
     * @param $orderId
     * @param int $documentType
     * @param int $preview
     * @param int $taxFree
     * @param int $temp
     * @return object
     */
    public function createDocument($orderId, $documentType = 1, $preview = 1, $taxFree = 0, $temp = 1)
    {
        return $this->client->get("/Order/createDocument?orderId={$orderId}&preview={$preview}&taxFree={$taxFree}&temp={$temp}&documentType={$documentType}");
    }

    /**
     * @param $orderId
     * @param string $invoiceNumber
     * @param string $displayDate
     * @param string $deliveryDate
     * @param string $vatId
     * @param int $taxFree
     * @param string $docComment
     * @param null $voucher
     * @param null $id
     * @return array|bool
     */
    public function generateDocumentInvoice(
        $orderId,
        $invoiceNumber = '',
        $displayDate = '',
        $deliveryDate = '',
        $vatId = '',
        $taxFree = 0,
        $docComment = '',
        $voucher = null,
        $id = null)
    {
        return $this->generateDocument(
            $orderId,
            1,
            $invoiceNumber,
            $displayDate,
            $deliveryDate,
            $vatId,
            $taxFree,
            $docComment,
            $voucher,
            $id);
    }

    /**
     * @param $orderId
     * @param string $invoiceNumber
     * @param string $displayDate
     * @param string $deliveryDate
     * @param string $vatId
     * @param int $taxFree
     * @param string $docComment
     * @param null $voucher
     * @param null $id
     * @return array|bool
     */
    public function generateDocumentDeliveryNote(
        $orderId,
        $invoiceNumber = '',
        $displayDate = '',
        $deliveryDate = '',
        $vatId = '',
        $taxFree = 0,
        $docComment = '',
        $voucher = null,
        $id = null)
    {
        return $this->generateDocument(
            $orderId,
            2,
            $invoiceNumber,
            $displayDate,
            $deliveryDate,
            $vatId,
            $taxFree,
            $docComment,
            $voucher,
            $id);
    }

    /**
     * @param $orderId
     * @param string $invoiceNumber
     * @param string $displayDate
     * @param string $deliveryDate
     * @param string $vatId
     * @param int $taxFree
     * @param string $docComment
     * @param null $voucher
     * @param null $id
     * @return array|bool
     */
    public function generateDocumentCreditNote(
        $orderId,
        $invoiceNumber = '',
        $displayDate = '',
        $deliveryDate = '',
        $vatId = '',
        $taxFree = 0,
        $docComment = '',
        $voucher = null,
        $id = null)
    {
        return $this->generateDocument(
            $orderId,
            3,
            $invoiceNumber,
            $displayDate,
            $deliveryDate,
            $vatId,
            $taxFree,
            $docComment,
            $voucher,
            $id);
    }


    /**
     * @param $orderId
     * @param string $invoiceNumber
     * @param string $displayDate
     * @param string $deliveryDate
     * @param string $vatId
     * @param int $taxFree
     * @param string $docComment
     * @param null $voucher
     * @param null $id
     * @return array|bool
     */
    public function generateDocumentReversalInvoice(
        $orderId,
        $invoiceNumber = '',
        $displayDate = '',
        $deliveryDate = '',
        $vatId = '',
        $taxFree = 0,
        $docComment = '',
        $voucher = null,
        $id = null)
    {
        return $this->generateDocument(
            $orderId,
            4,
            $invoiceNumber,
            $displayDate,
            $deliveryDate,
            $vatId,
            $taxFree,
            $docComment,
            $voucher,
            $id);
    }

    /**
     *
     * adadg
     * asd
     * @param $orderId
     * @param int $documentType 1: Invoice | 2: DeliveryNote | 3: CreditNote | 4: ReversalInvoice
     * @param string $invoiceNumber
     * @param string $displayDate
     * @param string $deliveryDate
     * @param string $vatId
     * @param int $taxFree
     * @param string $docComment
     * @param null $voucher
     * @param null $id
     * @return bool|array
     *
     * array (
     * 'id' => 12432,
     * 'date' => '2018-12-24',
     * 'typeId' => 2,
     * 'customerId' => 123,
     * 'orderId' => 36168,
     * 'amount' => 2.9900000000000002,
     * 'documentId' => 35834,
     * 'hash' => '049247763adsfdgfhgdfs',
     * );
     *
     * @throws \Exception
     */
    protected function generateDocument(
        $orderId,
        $documentType = 1,
        $invoiceNumber = '',
        $displayDate = '',
        $deliveryDate = '',
        $vatId = '',
        $taxFree = 0,
        $docComment = '',
        $voucher = null,
        $id = null)
    {
        $this->client->getCurl()->setHeader('Content-Type', 'application/json');
        $res = $this->client->post("/Order/createDocument/targetField/documents?_dc=" . $this->getTimestamp(), json_encode(
            [
                'orderId' => $orderId,
                'deliveryDate' => $deliveryDate,
                'displayDate' => $displayDate ? $displayDate : date('d.m.Y'),
                'vatId' => $vatId,
                'invoiceNumber' => $invoiceNumber,
                'documentType' => $documentType,
                'docComment' => $docComment,
                'voucher' => $voucher,
                'taxFree' => $taxFree,
                'id' => $id,
            ]
        ));

        $res = json_decode($this->wrapClassNames($res), true);

        // response control
        if (!is_array($res) || !$res['success']) return false;

        /*
         * Response is returned with full of orderdata
         * We only need to just create type document
         */
        $documents = $res['data'][0]['documents'];

        /*
         * Find the document just created
         */
        foreach ($documents as $document) {

            /*
             * Find by documentTypeId
             */
            if ($document['typeId'] == $documentType) {
                $isValidDate = (bool)strtotime($document['date']);
                if (!$isValidDate) {
                    /*
                     * change new Date(******) string to real date
                     */
                    $document['date'] = filter_var($document['date'], FILTER_SANITIZE_NUMBER_INT);
                    $document['date'] = date("Y-m-d", $document['date'] / 1000);
                }


                /*
                 * Remove unnecessary data
                 */
                unset($document['type']);
                unset($document['attribute']);


                return $document;
            }
        }

        return false;
    }

    /**
     * @param $documentId
     * @return mixed
     */
    public function deleteDocument($documentId)
    {
        return $this->client->post('/order/deleteDocument', ['documentId' => $documentId]);
    }

    /**
     * @param $documentId
     * @return mixed
     */
    public function ViisonPickwareERPOrderDocumentMailer_getMail($documentId)
    {
        return $this->client->post('/ViisonPickwareERPOrderDocumentMailer/getMail', ['documentId' => $documentId]);
    }

    /**
     * @param $documentId
     * @param array $mail
     * @return mixed
     * [
     * 'fromMail' => '',
     * 'fromName' => '',
     * 'toAddress' => '',
     * 'subject' => '',
     * 'content' => '',
     * 'attachment' => '',
     * 'isHtml' => false,
     * ]
     */
    public function ViisonPickwareERPOrderDocumentMailer_send($documentId, $mail = [])
    {
        $this->client->getCurl()->setHeader('Content-Type', 'application/json');
        return $this->client->post('/ViisonPickwareERPOrderDocumentMailer/getMail', json_encode(
            [
                'mail' => $mail,
                'documentId' => $documentId,
            ]
        ));
    }


    /**
     * @param $ordernumbers
     * @param bool $override
     * @param bool $createIfCompletelyPaid
     * @return array|bool
     */
    public function generateInvoices($ordernumbers, $override = false, $createIfCompletelyPaid = true)
    {
        if (!is_array($ordernumbers))
            $ordernumbers = array_map('trim', explode(',', $ordernumbers));

        if (!$ordernumbers) return false;


        $createdDocData = [];
        foreach ($ordernumbers as $ordernumber) {
            $order = $this->getOrderByNumber($ordernumber);

            $docs = $order['documents'];
            $hasInvoice = count(array_filter($docs, function ($doc) {
                    return $doc['typeId'] == 1; // invoice type
                })) > 0;

            if ((!$hasInvoice || $override) && ($order['cleared'] == 12 || !$createIfCompletelyPaid)) {
                $orderId = $order['id'];
                $createdDocData[$ordernumber] = $this->generateDocumentInvoice($orderId);
            }

        }

        return $createdDocData;
    }


}