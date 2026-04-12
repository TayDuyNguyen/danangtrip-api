<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class PaymentCallbackRequest
 * Validates the payment gateway callback data.
 * (Xác thực dữ liệu phản hồi từ cổng thanh toán)
 */
class PaymentCallbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * (Kiểm tra xem người dùng có quyền thực hiện yêu cầu này không)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * (Lấy các quy tắc xác thực áp dụng cho yêu cầu)
     */
    public function rules(): array
    {
        return [
            // transaction_code is required for our internal lookup
            'transaction_code' => ['sometimes', 'string', 'max:255'],

            // status is used to determine success/failure
            'status' => ['sometimes', 'string', 'max:50'],

            // Gateway specific fields (usually prefixed or specific names)
            // VNPAY
            'vnp_Amount' => ['sometimes'],
            'vnp_BankCode' => ['sometimes'],
            'vnp_BankTranNo' => ['sometimes'],
            'vnp_CardType' => ['sometimes'],
            'vnp_OrderInfo' => ['sometimes'],
            'vnp_PayDate' => ['sometimes'],
            'vnp_ResponseCode' => ['sometimes'],
            'vnp_TmnCode' => ['sometimes'],
            'vnp_TransactionNo' => ['sometimes'],
            'vnp_TransactionStatus' => ['sometimes'],
            'vnp_TxnRef' => ['sometimes'],
            'vnp_SecureHash' => ['sometimes', 'string'],

            // MoMo
            'partnerCode' => ['sometimes', 'string'],
            'orderId' => ['sometimes', 'string'],
            'requestId' => ['sometimes', 'string'],
            'amount' => ['sometimes'],
            'orderInfo' => ['sometimes', 'string'],
            'orderType' => ['sometimes', 'string'],
            'transId' => ['sometimes'],
            'resultCode' => ['sometimes', 'integer'],
            'message' => ['sometimes', 'string'],
            'payType' => ['sometimes', 'string'],
            'responseTime' => ['sometimes'],
            'extraData' => ['sometimes', 'string'],
            'signature' => ['sometimes', 'string'],

            // ZaloPay
            'appid' => ['sometimes'],
            'apptransid' => ['sometimes'],
            'pmtid' => ['sometimes'],
            'bankcode' => ['sometimes'],
            'discountamount' => ['sometimes'],
            'checksum' => ['sometimes'],
            'mac' => ['sometimes'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     * (Lấy các thông báo lỗi cho các quy tắc xác thực đã định nghĩa)
     */
    public function messages(): array
    {
        return [
            'transaction_code.max' => 'Transaction code is too long.',
        ];
    }
}
