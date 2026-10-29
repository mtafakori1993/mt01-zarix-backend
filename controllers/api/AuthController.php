<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\models\OtpRequestForm;
use app\models\OtpVerifyForm;
use app\services\GhasedakService;
use Yii;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\Response;

/**
 * REST API controller for OTP-based authentication.
 *
 * POST /api/auth/request-otp  — Send OTP code to mobile
 * POST /api/auth/verify-otp   — Verify OTP code and return access token
 */
class AuthController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly GhasedakService $ghasedak,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => [
                    'http://localhost:7900',
                    'https://localhost:7900',
                    'http://mt01.ir',
                    'https://mt01.ir',
                ],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
            ],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'request-otp' => ['POST'],
                'verify-otp' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    /**
     * Force JSON response for all actions.
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return true;
    }

    /**
     * POST /api/auth/request-otp
     * Send an OTP code to the given mobile number via Ghasedak SMS.
     *
     * Request body: { "mobile": "09123456789" }
     */
    public function actionRequestOtp(): array
    {
        $model = new OtpRequestForm($this->ghasedak);

        if ($model->load(Yii::$app->request->post(), '') && $model->send()) {
            return [
                'isSuccess' => true,
                'statusCode' => 200,
                'message' => 'کد تأیید با موفقیت ارسال شد.',
            ];
        }

        Yii::$app->response->statusCode = 400;

        return [
            'isSuccess' => false,
            'statusCode' => 400,
            'message' => 'ارسال کد تأیید ناموفق بود.',
            'errors' => $model->getErrors(),
        ];
    }

    /**
     * POST /api/auth/verify-otp
     * Verify the OTP code and authenticate the user.
     *
     * Request body: { "mobile": "09123456789", "otpCode": "1234" }
     */
    public function actionVerifyOtp(): array
    {
        $model = new OtpVerifyForm();

        if ($model->load(Yii::$app->request->post(), '') && ($result = $model->verify())) {
            return [
                'isSuccess' => true,
                'statusCode' => 200,
                'message' => 'ورود موفقیت‌آمیز بود.',
                'data' => $result,
            ];
        }

        Yii::$app->response->statusCode = 400;

        return [
            'isSuccess' => false,
            'statusCode' => 400,
            'message' => 'تأیید کد ناموفق بود.',
            'errors' => $model->getErrors(),
        ];
    }
}
