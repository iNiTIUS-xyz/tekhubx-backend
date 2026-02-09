<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LegalController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Client\TalentController;
use App\Http\Controllers\Common\CommonController;
use App\Http\Controllers\Common\ReportController;
use App\Http\Controllers\Common\StripeController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Common\PaymentController;
use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\DefaultAuthController;
use App\Http\Controllers\Client\WorkOrderController;
use App\Http\Controllers\Admin\ServiceFeesController;
use App\Http\Controllers\Auth\ProviderAuthController;
use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Auth\AccountManageController;
use App\Http\Controllers\Client\PoolDetailsController;
use App\Http\Controllers\Common\BankAccountController;
use App\Http\Controllers\Common\ChatMessageController;
use App\Http\Controllers\Admin\Country\StateController;
use App\Http\Controllers\Admin\DocumentationController;
use App\Http\Controllers\Client\SubscriptionController;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Http\Controllers\Admin\Country\CountryController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\service\ServiceController;
use App\Http\Controllers\Admin\Support\SupportController;
use App\Http\Controllers\Provider\CounterOfferController;
use App\Http\Controllers\Admin\frontend\FAQ\FAQController;
use App\Http\Controllers\Client\project\ProjectController;
use App\Http\Controllers\Client\WorkOrderManageController;
use App\Http\Controllers\Admin\category\CategoryController;
use App\Http\Controllers\Admin\TransactionReportController;
use App\Http\Controllers\Provider\ExpenseRequestController;
use App\Http\Controllers\Admin\frontend\Blog\BlogController;
use App\Http\Controllers\Admin\frontend\Page\PageController;
use App\Http\Controllers\Admin\frontend\Team\TeamController;
use App\Http\Controllers\Client\AdditionalContactController;
use App\Http\Controllers\Client\location\LocationController;
use App\Http\Controllers\Client\template\TemplateController;
use App\Http\Controllers\Provider\PayChangeRequestController;
use App\Http\Controllers\Admin\frontend\Brand\BrandController;
use App\Http\Controllers\Admin\frontend\Quote\QuoteController;
use App\Http\Controllers\Admin\frontend\SocialMediaController;
use App\Http\Controllers\Provider\WorkOrderCheckoutController;
use App\Http\Controllers\Admin\frontend\Slider\SliderController;
use App\Http\Controllers\Admin\frontend\WorkStepDetailController;
use App\Http\Controllers\Admin\frontend\FrontendProjectController;
use App\Http\Controllers\Admin\frontend\Page\PageHeaderController;
use App\Http\Controllers\Admin\frontend\Page\PageParagraphController;
use App\Http\Controllers\Admin\qualification\QualificationController;
use App\Http\Controllers\Admin\frontend\HowItWorks\WorkingStepController;
use App\Http\Controllers\Provider\WorkRequest\SendWorkRequestsController;
use App\Http\Controllers\Admin\frontend\Testimonial\TestimonialController;
use App\Http\Controllers\Client\defaultClient\DefaultClientListController;
use App\Http\Controllers\Provider\EmployeeProvider\EmployeeProviderController;
use App\Http\Controllers\Admin\qualification\QualificationSubCategoryController;
use App\Http\Controllers\Admin\frontend\FrontendService\FrontendServiceController;
use App\Http\Controllers\Provider\EmployeeProvider\LicenseAndCertificateController;
use App\Http\Controllers\Admin\frontend\FrontendService\FrontendServiceCategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('slider-list', [FrontendController::class, 'sliderList']);
Route::get('brand-list', [FrontendController::class, 'brandList']);
Route::get('blog-list', [FrontendController::class, 'blogList']);
Route::get('our-project-list', [FrontendController::class, 'ourProjectList']);
Route::get('blog-details/{slug}', [FrontendController::class, 'blogDetails']);
Route::get('our-project-details/{slug}', [FrontendController::class, 'ourProjectDetails']);
Route::get('team-list', [FrontendController::class, 'teamList']);
Route::get('work-step-detail', [FrontendController::class, 'workStepDetails']);
Route::get('frontend/service', [FrontendController::class, 'frontendService']);
Route::get('frontend/service-category', [FrontendController::class, 'frontendServiceCategory']);
Route::get('frontend/service-category/{slug}/details', [FrontendController::class, 'serviceCategoryDetails']);
Route::get('frontend/service/{slug}/details', [FrontendController::class, 'serviceDetails']);
Route::post('partner-contact', [FrontendController::class, 'partnerContactStore']);
Route::post('contact-us', [FrontendController::class, 'contactUs']);
Route::get('pages-list', [FrontendController::class, 'pages']);
Route::get('pages-list-view/{slug}', [FrontendController::class, 'pagesDetails']);
Route::get('tekhubx/supports', [FrontendController::class, 'tekhubxSupports']);
Route::get('tekhubx/supports/{slug}/details', [FrontendController::class, 'tekhubxSupportsDetails']);
Route::get('subscription-plan-details', [FrontendController::class, 'subscription_plans']);
Route::get('testimonial-list', [FrontendController::class, 'testimonialsList']);
Route::get('page-headers-list', [PageHeaderController::class, 'index']);
Route::get('page-paragraphs-list', [PageParagraphController::class, 'index']);
Route::get('faqs-list', [FrontendController::class, 'faqList']);
Route::get('how-it-works-steps-list', [WorkingStepController::class, 'index']);
Route::get('social-media', [SocialMediaController::class, 'index']);
Route::get('get-our-project-details', [FrontendProjectController::class, 'index']);
//Country
Route::resource('country', CountryController::class);
Route::resource('state', StateController::class);
Route::get('country-list-forntend', [CommonController::class, 'country_data']);
Route::get('state-wise-zip-code', [CommonController::class, 'state_wise_zip_code']);

//password reset
Route::post('forgot-password', [ClientAuthController::class, 'forgotPassword']);
Route::post('reset-password', [ClientAuthController::class, 'resetPassword']);

// login & Register
Route::group(['prefix' => 'auth'], function () {
    Route::post('admin/login', [AdminAuthController::class, 'login']);
    // provider
    Route::post('provider/register', [ProviderAuthController::class, 'register']);
    // client
    Route::post('client/login', [ClientAuthController::class, 'login']);
    // login
    Route::post('user/login', [ClientAuthController::class, 'login']);
    Route::post('refresh-token', [ClientAuthController::class, 'refreshToken']);
    Route::post('client/register', [ClientAuthController::class, 'register']);
    // client manager password setup
    Route::post('/setup-password', [DefaultAuthController::class, 'setupPassword']);
    // provider password setup
    // Route::post('setup-provider-password', [DefaultAuthController::class, 'setupProviderPassword']);
});
// logout
Route::post('admin/logout', [AdminAuthController::class, 'logout']);
// Admin
Route::group(['middleware' => 'auth.admin'], function () {
    // profile and dashboard
    Route::get('admin/dashboard', [AdminAuthController::class, 'allUserDetails'])->name('admin.dashboard');
    Route::get('admin/user/{id}/details', [AdminAuthController::class, 'individualUserDetails'])->name('admin.user.details');
    Route::post('user-toggle/{id}', [AdminAuthController::class, 'user_status'])->name('admin.user.status');


    Route::get('admin/profile/info', [AdminAuthController::class, 'profileInfo'])->name('admin.profile.info');
    Route::post('admin/profile/update', [AdminAuthController::class, 'profileUpdate'])->name('admin.profile.update');
    Route::post('admin/password/change', [AdminAuthController::class, 'passwordChange'])->name('admin.password.change');
    // global work-category
    Route::get('/work-category', [CategoryController::class, 'ViewWorkCategory'])->name('work-category');
    Route::post('/work-category', [CategoryController::class, 'WorkCategory'])->name('work-category.store');
    Route::get('/work-category/edit/{id}', [CategoryController::class, 'EditWorkCategory'])->name('work-category.edit');
    Route::post('/work-category/update/{id}', [CategoryController::class, 'UpdateWorkCategory'])->name('work-category.update');
    Route::delete('/work-categories/{id}', [CategoryController::class, 'deleteCategory'])->name('work-category.destroy');
    // work-subcategory
    Route::get('/work-subcategory', [CategoryController::class, 'ViewWorkSubCategory'])->name('work-subcategory');
    Route::post('/work-subcategory', [CategoryController::class, 'WorkSubCategory'])->name('work-subcategory.store');
    Route::get('/work-subcategory/edit/{id}', [CategoryController::class, 'EditWorkSubCategory'])->name('work-subcategory.edit');
    Route::post('/work-subcategory/update/{id}', [CategoryController::class, 'UpdateWorkSubCategory'])->name('work-subcategory.update');
    Route::delete('/work-subcategories/{id}', [CategoryController::class, 'deleteSubCategory'])->name('work-subcategory.destroy');

    // service
    Route::post('/service', [ServiceController::class, 'Service'])->name('service.store');
    Route::get('/service', [ServiceController::class, 'ViewService'])->name('service');
    Route::get('/service/edit/{id}', [ServiceController::class, 'EditService'])->name('service.edit');
    Route::post('/service/update/{id}', [ServiceController::class, 'UpdateService'])->name('service.update');
    Route::delete('/services/{id}', [ServiceController::class, 'deleteService'])->name('service.destroy');

    //Service Fees
    Route::get('service-fees', [ServiceFeesController::class, 'index'])->name('service-fees');
    Route::post('service-fees/store', [ServiceFeesController::class, 'store'])->name('service-fees.store');
    Route::get('service-fees/edit/{id}', [ServiceFeesController::class, 'edit'])->name('service-fees.edit');
    Route::post('service-fees/update/{id}', [ServiceFeesController::class, 'update'])->name('service-fees.update');
    Route::delete('/service-fees/{id}', [ServiceFeesController::class, 'deleteServiceFee'])->name('service-fees.destroy');

    //Qualification Type
    Route::resource('qualification', QualificationController::class);
    Route::resource('qualification-sub-cat', QualificationSubCategoryController::class);
    // blog
    Route::resource('blog', BlogController::class);
    Route::resource('our-project', FrontendProjectController::class);
    // testimonial
    Route::resource('testimonial', TestimonialController::class);
    // slider
    Route::resource('slider', SliderController::class);
    // brand
    Route::resource('brand', BrandController::class);
    // quote
    Route::resource('quote', QuoteController::class);
    // work step details and others

    Route::get('get-work-step-details/list', [WorkStepDetailController::class, 'workStepList'])->name('work-step-details.list');
    Route::post('get-work-step-details/store', [WorkStepDetailController::class, 'workStepStore'])->name('work-step-details.store');
    Route::get('get-work-step-details/edit/{id}', [WorkStepDetailController::class, 'workStepEdit'])->name('work-step-details.edit');
    Route::post('get-work-step-details/update/{id}', [WorkStepDetailController::class, 'workStepUpdate'])->name('work-step-details.update');
    Route::get('get-work-step-details/delete/{id}', [WorkStepDetailController::class, 'workStepDelete'])->name('work-step-details.delete');

    // partner contact
    Route::get('partner/contact-list', [WorkStepDetailController::class, 'partnerContactList'])->name('partner-contact.list');
    Route::get('partner/contact-list/{id}/view', [WorkStepDetailController::class, 'partnerContactView'])->name('partner-contact.view');
    Route::get('partner/contact-list/{id}/delete', [WorkStepDetailController::class, 'partnerContactDelete'])->name('partner-contact.delete');

    // contact us
    Route::get('contact-us-list', [WorkStepDetailController::class, 'contactUsList'])->name('contact-us.list');
    Route::get('contact-us-list/{id}/view', [WorkStepDetailController::class, 'contactUsView'])->name('contact-us.view');
    Route::get('contact-us-list/{id}/delete', [WorkStepDetailController::class, 'contactUsDelete'])->name('contact-us.delete');

    // frontend service category
    Route::resource('service-category', FrontendServiceCategoryController::class);
    // frontend service
    Route::resource('frontend-service', FrontendServiceController::class);
    // team
    Route::resource('team', TeamController::class);
    // pages
    Route::resource('pages', PageController::class);
    // page-headers
    Route::resource('page-headers', PageHeaderController::class);
    // page-paragraphs
    Route::resource('page-paragraphs', PageParagraphController::class);
    // faqs
    Route::resource('faqs', FAQController::class);
    // how it works
    Route::resource('how-it-works-steps', WorkingStepController::class);
    //Plan
    Route::resource('plan', PlanController::class);
    //Country
    Route::resource('country', CountryController::class);
    // state
    Route::resource('state', StateController::class);
    // legal info
    Route::resource('legal', LegalController::class);
    //expense category
    Route::resource('expense-category', ExpenseCategoryController::class);
    // supports
    Route::resource('supports', SupportController::class);
    // others

    Route::get('get-subscription-details', [TransactionReportController::class, 'getSubscription'])->name('get-subscription-details');
    Route::get('get-client-payment-details', [TransactionReportController::class, 'getPayment'])->name('get-client-payment-details');
    Route::get('get-provider-payment-details', [TransactionReportController::class, 'getProviderPaymentShowInAdmin'])->name('get-provider-payment-details');
    Route::get('get-client-point-request', [TransactionReportController::class, 'pointRequestByClient'])->name('get-client-point-request');
    Route::post('update-client-point-request/{id}', [TransactionReportController::class, 'pointRequestUpdate'])->name('update-client-point-request');

    Route::post('license-and-certificate-approval', [TransactionReportController::class, 'licenseAndCertificateApproval'])->name('license-and-certificate-approval');
    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    // staff
    Route::resource('staff', StaffController::class);

    Route::prefix('feedback-reason')->group(function () {
        Route::post('/', [FeedbackController::class, 'reason_store'])->name('reason.store');
        Route::get('/{id}', [FeedbackController::class, 'reason_edit'])->name('reason.edit');
        Route::put('/{id}', [FeedbackController::class, 'reason_update'])->name('reason.update');
        Route::delete('/{id}', [FeedbackController::class, 'reason_destroy'])->name('reason.destroy');
    });
    Route::delete('/feedback/{id}', [FeedbackController::class, 'deleteFeedback'])->name('feedback.destroy');
    // documentation
    Route::resource('documentation', DocumentationController::class);
    Route::get('parent/documentation-list', [DocumentationController::class, 'parent'])->name('parent.documentation.list');

    //social media
    Route::get('all-social-media', [SocialMediaController::class, 'index'])->name('social-media.index');
    Route::post('/social-media', [SocialMediaController::class, 'store'])->name('social-media.store');
    Route::get('/social-media/{id}', [SocialMediaController::class, 'show'])->name('social-media.show');
    Route::put('/social-media/{id}', [SocialMediaController::class, 'update'])->name('social-media.update');
    Route::delete('/social-media/{id}', [SocialMediaController::class, 'destroy'])->name('social-media.destroy');

    Route::get('admin/payment-settings', [PaymentSettingController::class, 'index']);
    Route::get('admin/payment-settings/{id}', [PaymentSettingController::class, 'show']);
    Route::post('admin/payment-settings', [PaymentSettingController::class, 'store']);
    Route::post('admin/payment-settings', [PaymentSettingController::class, 'update']);
    // contact setting
    Route::get('admin/settings', [SettingController::class, 'index']);
    Route::get('admin/settings/{id}', [SettingController::class, 'edit']);
    Route::post('admin/settings', [SettingController::class, 'update']);
});

//Client
Route::group(['middleware' => 'auth.client'], function () {
    //dashboard
    Route::get('client-dashboard', [CommonController::class, 'clientDashboard'])->name('client.dashboard');
    Route::get('work-order-history/{work_order_unique_id}', [CommonController::class, 'clinetHistoryLog'])->name('work.order.history');
    Route::get('graph-data', [CommonController::class, 'graphData'])->name('graph.data');

    //location
    Route::get('location', [LocationController::class, 'index'])->name('location.index');
    Route::post('location', [LocationController::class, 'store'])->name('location.store');
    Route::get('location/edit/{id}', [LocationController::class, 'edit'])->name('location.edit');
    Route::post('location/update/{id}', [LocationController::class, 'update'])->name('location.update');
    Route::post('import/file/location', [LocationController::class, 'locationImport'])->name('import.location');
    Route::get('download/file/location', [LocationController::class, 'download'])->name('download.location');

    Route::delete('location/{id}', [LocationController::class, 'destroy'])->name('location.destroy');
    //Project
    Route::get('/project', [ProjectController::class, 'index']);
    Route::post('/project', [ProjectController::class, 'store']);
    Route::get('/project/edit/{id}', [ProjectController::class, 'edit']);
    Route::post('/project/update/{id}', [ProjectController::class, 'update']);
    Route::post('fine-project', [ProjectController::class, 'findProject']);
    Route::delete('project/{id}', [ProjectController::class, 'destroy'])->name('project.destroy');
    //Template
    Route::resource('template', TemplateController::class);
    Route::post('find-template', [TemplateController::class, 'FindTemplate']);
    //Default Client
    Route::resource('default-client', DefaultClientListController::class);
    Route::post('default-client/{id}', [DefaultClientListController::class, 'update']);

    Route::post('import/default-client', [DefaultClientListController::class, 'import']);
    Route::get('excel/download', [DefaultClientListController::class, 'excelDownload']);
    //Work Order Manage
    Route::resource('work-order-manage', WorkOrderManageController::class);
    //AdditionalContact
    Route::resource('additional-contact', AdditionalContactController::class);
    //work-order
    Route::resource('work-order', WorkOrderController::class);
    Route::post('work-order/{id}', [WorkOrderController::class, 'update']);
    Route::post('work-order-status-update/{id}', [WorkOrderController::class, 'work_order_status_update']);
    Route::get('get-additional-locations', [WorkOrderController::class, 'getAdditionalLocation']);
    Route::get('get-documents', [WorkOrderController::class, 'getDocuments']);
    Route::get('get-all-documents-work-order-wise', [WorkOrderController::class, 'getDocumentsTwo']);
    Route::get('get-timezone', [WorkOrderController::class, 'getTimezoneByCoordinates']);
    Route::get('sub-category-wise-service/{id}', [WorkOrderController::class, 'subCategoryWiseService']);


    // counter offer
    Route::get('work/order/{work_unique_id}/counter-offer', [WorkOrderController::class, 'counterOfferList']);
    Route::get('work/order/{work_unique_id}/counter-offer/{id}', [WorkOrderController::class, 'viewCounterOffer']);
    Route::post('work/order/{work_unique_id}/counter-offer/assign/{id}', [WorkOrderController::class, 'assignedCounterOfferWorkOrder']);

    // send work request
    Route::get('work/order/{work_unique_id}/send-work-request', [WorkOrderController::class, 'workRequestList']);
    Route::get('work/order/{work_unique_id}/send-work-request/{id}', [WorkOrderController::class, 'viewWorkRequest']);
    Route::post('work/order/{work_unique_id}/assign/{id}', [WorkOrderController::class, 'assignedWorkOrder']);

    // expense request
    Route::get('work/order/{work_unique_id}/expense-request', [WorkOrderController::class, 'expenseRequestList']);
    Route::get('work/order/{work_unique_id}/expense-request/{id}', [WorkOrderController::class, 'viewExpenseRequest']);
    Route::post('work/order/{work_unique_id}/expense-request/approve/{id}', [WorkOrderController::class, 'expenseRequestApprove']);

    // pay change
    Route::get('work/order/{work_unique_id}/pay-change', [WorkOrderController::class, 'payChangeList']);
    Route::get('work/order/{work_unique_id}/pay-change/{id}', [WorkOrderController::class, 'viewPayChange']);
    Route::post('work/order/{work_unique_id}/pay-change/approve/{id}', [WorkOrderController::class, 'payChangeWorkOrder']);

    // work order problem
    Route::get('work-order-report-problem/{work_order_unique_id}', [WorkOrderController::class, 'workOrderReportProblemShow']);

    // talent
    Route::resource('talent', TalentController::class);
    // Pool Details
    Route::resource('pool-details', PoolDetailsController::class);
    Route::get('get-talent-wise-provider/{id}', [PoolDetailsController::class, 'talentNameWiseProvider']);
    //Subscription
    Route::resource('subscription', SubscriptionController::class);

    Route::post('request-for-point', [SubscriptionController::class, 'sendRequestForPoint']);
    Route::get('get-subscription-data-for-client', [SubscriptionController::class, 'getSubscriptionDataForClient']);
    Route::get('point-balance', [PaymentController::class, 'clientPointBalance']);
    Route::post('subscription-cancel', [SubscriptionController::class, 'cancelSubscription']);

    // employee license & certificates
    Route::resource('employee-provider/license-certificates', LicenseAndCertificateController::class);
    // work order mark complete
    Route::post('work-order-review-by-client', [WorkOrderController::class, 'reviewByClient']);
    Route::get('work-order-complete-file/{work_order_unique_id}', [WorkOrderController::class, 'getWorkOrderCompleteFile']);
    Route::post('work-order-payment-by-client', [StripeController::class, 'clientWorkOrderPayment']);

    //fund details
    Route::get('client-fund-details', [ReportController::class, 'fundDetails']);
});

//Provider
Route::group(['middleware' => 'auth.provider'], function () {
    //work order check out
    Route::post('work-order-checkout-start-time/{work_order_unique_id}', [WorkOrderCheckoutController::class, 'startTime']);
    Route::post('work-order-checkout-confirm/{work_order_unique_id}', [WorkOrderCheckoutController::class, 'confirmWorkOrder']);
    Route::post('work-order-checkout-on-my-way/{work_order_unique_id}', [WorkOrderCheckoutController::class, 'markOnMyWay']);
    Route::post('work-order-checkout-check-in/{work_order_unique_id}', [WorkOrderCheckoutController::class, 'checkIn']);

    // employee provider
    Route::resource('employee-providers', EmployeeProviderController::class);
    Route::post('employee-providers-profile-image-update', [EmployeeProviderController::class, 'profileImage']);
    Route::post('license-and-certificate-update/{id}', [EmployeeProviderController::class, 'licenseAndCertificate']);
    // send work requests
    Route::resource('send-work-requests', SendWorkRequestsController::class); //middleware done
    // counter offer
    Route::resource('counter-offer', CounterOfferController::class); //middleware done
    // expense request
    Route::resource('expense-request', ExpenseRequestController::class); //middleware done
    Route::get('work-order-wise-expense-request/{work_order_unique_id}', [ExpenseRequestController::class, 'workOrderExpenseRequest']);
    // pay change request
    Route::resource('pay-change-request', PayChangeRequestController::class); //middleware done

    Route::get('work-order-wise-expense-request/{work_order_unique_id}', [ExpenseRequestController::class, 'workOrderExpenseRequest']);
    Route::get('work-order-wise-pay-change-request/{work_order_unique_id}', [PayChangeRequestController::class, 'workOrderPayChangeRequest']);
    // license certificate
    Route::get('license-certificate-details', [EmployeeProviderController::class, 'licenseAndCertificateListDetails']);
    Route::post('licence-certificate-check', [SendWorkRequestsController::class, 'work_order_id_wise_employed_provider']);
    // report controller
    Route::get('provider-work-report', [ReportController::class, 'providerWorkReport']);

    //export work order
    Route::post('export-work-order', [WorkOrderController::class, 'exportWorkOrder']);

    // provider review & payment
    Route::post('work-order-review-by-provider', [WorkOrderController::class, 'reviewByProvider']);
    Route::get('get-review-by-provider', [WorkOrderController::class, 'getReviewByProvider']);
    Route::post('update-review-by-provider', [WorkOrderController::class, 'updateReviewByProvider']);

    Route::post('work-order-mark-complete-by-provider/{work_order_unique_id}', [WorkOrderController::class, 'providerMarkComplete']);
    Route::get('get-payment-details', [TransactionReportController::class, 'getProviderPaymentShowInProvider']);
    Route::post('report-problem/{work_order_unique_id}', [WorkOrderController::class, 'providerSendReportProblem']);
    Route::post('not-interest/{work_order_unique_id}', [WorkOrderController::class, 'notInterest']);

    //withdraw
    Route::post('provider-withdraw', [StripeController::class, 'requestWithdrawal']);
    Route::get('provider-withdraw-data', [ReportController::class, 'providerWithdrawData']);
    Route::get('provider-payment-details', [ReportController::class, 'providerPaymentDetails']);
});

//common
Route::group(['middleware' => ['check.user.guard']], function () {
    // dashboard
    Route::get('dashboard', [ClientAuthController::class, 'userProfile']);
    Route::post('user/logout', [ClientAuthController::class, 'logout']);
    // providers
    Route::get('providers', [EmployeeProviderController::class, 'providersList']);
    Route::get('providers/{id}/details', [EmployeeProviderController::class, 'providersDetails']);
    //get country details
    Route::get('country-list', [CommonController::class, 'country_data']);
    Route::post('company-update', [CommonController::class, 'companyUpdate']);
    Route::post('profile/update', [CommonController::class, 'updateUserProfile']);
    Route::post('check-username-exists', [CommonController::class, 'checkUsername']);
    Route::post('license-and-certificate-update', [CommonController::class, 'licenseAndCertificateCommon']);
    // bank-account
    Route::resource('bank-account', BankAccountController::class); //middleware done
    // qualification
    Route::get('qualification-type', [CommonController::class, 'qualification_type']);
    Route::post('qualification-wise-category', [CommonController::class, 'type_wise_cat']);
    Route::get('qualification-list-for-license-certificate', [CommonController::class, 'qualification_list_of_license_and_certificate']);
    // account manage
    Route::post('change-email', [AccountManageController::class, 'changeEmail']);
    Route::post('account-deletion', [AccountManageController::class, 'accountDeletion']);
    // work category
    Route::get('work-category-list', [CategoryController::class, 'ViewWorkCategory']);
    Route::get('service-list', [ServiceController::class, 'ViewService']);
    // work-order
    Route::get('work-order-list', [WorkOrderController::class, 'all_work_order']);
    Route::post('work-order-details/{work_order_unique_id}', [WorkOrderController::class, 'single_work_order']);
    Route::get('single-work-order-details/{work_order_unique_id}', [WorkOrderController::class, 'single_work_order']);
    Route::get('work-order-review/{work_order_unique_id}', [WorkOrderController::class, 'getReview']);
    //chat-sms
    Route::get('message/user-list/{work_order_unique_id}', [ChatMessageController::class, 'messageUserList']);
    Route::post('chat/assign-provider', [ChatMessageController::class, 'assignProviderChat']);
    Route::get('chat/{work_order_unique_id}/messages', [ChatMessageController::class, 'fetchMessages']);
    Route::post('chat/{work_order_unique_id}/send', [ChatMessageController::class, 'sendMessage']);
    Route::get('chat/list', [ChatMessageController::class, 'chatList']);
    Route::get('get/notification', [ChatMessageController::class, 'getNotification']);
    Route::post('notification-read/{id}', [ChatMessageController::class, 'notificationRead']);
    Route::post('notifications/read-all', [ChatMessageController::class, 'notificationReadAll']);

    //paypal
    Route::post('subscription/payment', [StripeController::class, 'purchaseSubscription']);
    Route::get('plan-details', [PlanController::class, 'index']);
    Route::post('feedback', [FeedbackController::class, 'store']);

    //stripe connect
    Route::post('stripe-connect', [StripeController::class, 'stripeConnect']);
    // Route::get('stripe-complete', [StripeController::class, 'stripeCallback']);
    // Route::get('stripe-connection/callback', [StripeController::class, 'stripeCallbackTwo'])->name('stripe.callback');

    Route::post('stripe-payment-method-store', [StripeController::class, 'storePaymentMethod']);
    Route::post('micro-deposits-verify', [StripeController::class, 'verifyMicrodeposits']);

    // delete stripe account
    Route::delete('delete-stripe-account', [StripeController::class, 'deleteStripeAccount']);
    // Route::get('test-avalara', [CommonController::class, 'testAvalara']);

    // Update provider location
    Route::post('location-update', [WorkOrderCheckoutController::class, 'updateLocation']);

    // Get latest location for a work order
    Route::get('work-orders/{work_order_unique_id}/location', [WorkOrderCheckoutController::class, 'getLatestLocation']);

    // Get location history for a work order
    Route::get('work-orders/{work_order_unique_id}/location-history', [WorkOrderCheckoutController::class, 'getLocationHistory']);

    Route::prefix('feedback-reason')->group(function () {
        Route::get('/', [FeedbackController::class, 'reason_index']);
    });
});

Route::group(['middleware' => ['auth.provider', 'auth.client', 'auth.admin']], function () {
    // role
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles/store', [RoleController::class, 'store']);
    Route::get('roles/{id}/edit', [RoleController::class, 'edit']);
    Route::post('roles/{id}/update', [RoleController::class, 'update']);
    Route::get('roles/{id}/destroy', [RoleController::class, 'destroy']);
    Route::get('permissions-list', [RoleController::class, 'permissions']);
    // my permission
    Route::get('my-permission', [RoleController::class, 'myPermission']);
});

// paypal
Route::post('paypal/webhook', [PaymentController::class, 'paypalHandleWebhook'])->name('paypal.webhook');
Route::get('paypal/success', [PaymentController::class, 'paypal_success'])->name('paypal.success');
Route::get('paypal/cancel', [PaymentController::class, 'paypal_cancel'])->name('paypal.cancel');

//stripe
Route::post('stripe/webhook-response', [StripeController::class, 'handleWebhook']);
Route::get('stripe/success', [StripeController::class, 'stripe_success'])->name('stripe.success');
Route::get('stripe/cancel', [StripeController::class, 'stripe_cancel'])->name('stripe.cancel');

//data entry

// Route::get('data-entry', [FrontendController::class, 'importTechniciansFromExcel']);
// Route::post('stripe/webhook-test', function () {
//     Log::channel('payment_log')->info('Test webhook route reached');
//     return response('ok', 200);
// });
