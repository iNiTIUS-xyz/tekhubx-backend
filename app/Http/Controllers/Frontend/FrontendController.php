<?php

namespace App\Http\Controllers\Frontend;

use App\Models\FAQ;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Brand;
use App\Models\Slider;
use App\Models\Profile;
use App\Models\Support;
use App\Models\ContactUs;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\PartnerContact;
use App\Models\WorkStepDetail;
use App\Utils\ServerErrorMask;
use App\Models\FrontendProject;
use App\Models\FrontendService;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\FrontendServiceCategory;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Admin\BlogResource;
use App\Http\Resources\Admin\PageResource;
use App\Http\Resources\Admin\TeamResource;
use App\Http\Resources\OurProjectResource;
use App\Http\Resources\Admin\BrandResource;
use App\Http\Resources\Admin\SliderResource;
use App\Http\Resources\Admin\SupportResource;
use App\Http\Resources\Admin\ContactUsResource;
use App\Http\Resources\Admin\TestimonialResource;
use App\Http\Resources\Admin\PartnerContactResource;
use App\Http\Resources\Admin\FrontendServiceResource;
use App\Http\Resources\Admin\WorkStepDetailsResource;
use App\Http\Resources\Admin\FrontendServiceCategoryResource;

class FrontendController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function sliderList()
    {
        try {
            $sliders = Slider::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'sliders' => SliderResource::collection($sliders),
            ]);
        } catch (\Exception $e) {
            Log::error('Slider query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function brandList()
    {
        try {
            $brands = Brand::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'brands' => BrandResource::collection($brands),
            ]);
        } catch (\Exception $e) {
            Log::error('Brand query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function blogList()
    {
        try {
            $blogs = Blog::query()
                ->where('status', 'Active')
                ->with('admin')
                ->get();

            return response()->json([
                'status' => 'success',
                'blogs' => BlogResource::collection($blogs),
            ]);
        } catch (\Exception $e) {

            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function blogDetails($slug)
    {
        try {
            $blogs = Blog::query()
                ->where('slug', $slug)
                ->where('status', 'Active')
                ->with('admin')
                ->first();


            if (!$blogs) {

                return response()->json([
                    'status' => 'success',
                    'message' => "Blog dose not exist",
                ]);
            }

            $blogs->total_view += 1;
            $blogs->save();

            return response()->json([
                'status' => 'success',
                'blogs' => new BlogResource($blogs),
            ]);
        } catch (\Exception $e) {
            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function ourProjectList()
    {
        try {
            $projects = FrontendProject::query()
                ->where('status', 'Active')
                ->with('admin')
                ->get();

            return response()->json([
                'status' => 'success',
                'projects' => OurProjectResource::collection($projects),
            ]);
        } catch (\Exception $e) {

            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function ourProjectDetails($slug)
    {
        try {
            $projects = FrontendProject::query()
                ->where('slug', $slug)
                ->where('status', 'Active')
                ->with('admin')
                ->first();


            if (!$projects) {

                return response()->json([
                    'status' => 'success',
                    'message' => "Blog dose not exist",
                ]);
            }

            $projects->total_view += 1;
            $projects->save();

            return response()->json([
                'status' => 'success',
                'projects' => new OurProjectResource($projects),
            ]);
        } catch (\Exception $e) {
            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function teamList()
    {
        try {
            $teams = Team::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'teams' => TeamResource::collection($teams),
            ]);
        } catch (\Exception $e) {
            Log::error('Blog query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }


    public function workStepDetails()
    {
        try {

            $workStepDetail = WorkStepDetail::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'work_step' => WorkStepDetailsResource::collection($workStepDetail),
            ]);
        } catch (\Exception $e) {
            Log::error('Work step query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function frontendService()
    {
        try {
            $frontendService = FrontendService::query()
                ->where('status', 'Active')
                ->with('frontendServiceCategory')
                ->get();

            return response()->json([
                'status' => 'success',
                'frontendService' => FrontendServiceResource::collection($frontendService),
            ]);
        } catch (\Exception $e) {
            Log::error('Frontend Service not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function frontendServiceCategory()
    {
        try {
            $serviceCategory = FrontendServiceCategory::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'service_category' => FrontendServiceCategoryResource::collection($serviceCategory),
            ]);
        } catch (\Exception $e) {
            Log::error('Service Category not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function serviceCategoryDetails($slug)
    {
        try {
            $serviceCategory = FrontendServiceCategory::query()
                ->where('slug', $slug)
                ->where('status', 'Active')
                ->with(['frontendService'])
                ->first();

            if (!$serviceCategory) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'frontendService' => new FrontendServiceCategoryResource($serviceCategory),
            ]);
        } catch (\Exception $e) {
            Log::error('Frontend Service category not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function serviceDetails($slug)
    {
        try {
            $service = FrontendService::query()
                ->where('slug', $slug)
                ->where('status', 'Active')
                ->first();

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'frontendService' => new FrontendServiceResource($service),
            ]);
        } catch (\Exception $e) {
            Log::error('Frontend Service category not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function partnerContactStore(Request $request)
    {
        $rules = [
            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'company_name' => 'required|max:50',
            'email' => 'required|max:50|unique:partner_contacts,email',
            'about_your_company' => 'required|max:250',
            'partnership_interested' => 'required|in:Platform Management,Technology,Referral,Other',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }
        try {

            $partnerContact = new PartnerContact();
            $partnerContact->first_name = $request->first_name;
            $partnerContact->last_name = $request->last_name;
            $partnerContact->company_name = $request->company_name;
            $partnerContact->email = $request->email;
            $partnerContact->about_your_company = $request->about_your_company;
            $partnerContact->partnership_interested = $request->partnership_interested;
            $partnerContact->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Partnering Contact Request Form Submission Successfully Done',
                'partner_contact' => new PartnerContactResource($partnerContact->refresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Partner contact store error' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function contactUs(Request $request)
    {
        $rules = [
            'name' => 'required|max:50',
            'email' => 'required|max:50',
            'phone' => 'required',
            'message' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::VALIDATION_ERROR, $validator->errors()->toArray());
            return response()->json([
                'errors' => $formattedErrors,
            ], 422);
        }
        try {

            $contactUs = new ContactUs();
            $contactUs->first_name = $request->name;
            $contactUs->email = $request->email;
            $contactUs->phone = $request->phone;
            $contactUs->message = $request->message;
            $contactUs->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Contact Us Request Form Submission Successfully Done',
                'contact_us' => new ContactUsResource($contactUs->refresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Contact store error' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function pages()
    {
        try {
            $pages = Page::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'pages' => PageResource::collection($pages),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }
    public function pagesDetails($slug)
    {

        try {
            $pages = Page::query()
                ->where('page_slug', $slug)
                ->where('status', 'Active')
                ->first();

            return response()->json([
                'status' => 'success',
                'pages' => new PageResource($pages),
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function tekhubxSupports(Request $request)
    {
        try {
            $supports = Support::query()
                ->when($request->keyword, fn($q) => $q->where('title', 'LIKE', "%" . $request->keyword . "%")->orWhere('slug', 'LIKE', "%" . $request->keyword . "%"))
                ->when($request->support_for, fn($q) => $q->where('support_for', $request->support_for))
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'supports' => SupportResource::collection($supports),
            ]);
        } catch (\Exception $e) {
            Log::error('Support query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function tekhubxSupportsDetails($slug)
    {
        try {
            $supports = Support::query()
                ->where('slug', $slug)
                ->first();

            $relatedSupports = Support::query()
                ->where('status', 'Active')
                ->where('title', 'LIKE', "%" . $slug . "%")
                ->orWhere('slug', 'LIKE', "%" . $slug . "%")
                ->get();

            if (!$supports) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Support dose not exist'
                ]);
            }
            return response()->json([
                'status' => 'success',
                'related_supports' => SupportResource::collection($relatedSupports),
                'supports' => new SupportResource($supports),
            ]);
        } catch (\Exception $e) {
            Log::error('Support query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }

    public function subscription_plans()
    {
        try {

            $plan = Plan::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'plan' => PlanResource::collection($plan),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }

    public function testimonialsList()
    {
        try {

            $testimonial = Testimonial::query()
                ->where('status', 'Active')
                ->get();

            return response()->json([
                'status' => 'success',
                'testimonials' => TestimonialResource::collection($testimonial),
            ]);
        } catch (\Exception $error) {
            Log::error($error);
            $formattedErrors = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::SERVER_ERROR]);
            return response()->json([
                'errors' => $formattedErrors,
                'payload' => null,
            ], 500);
        }
    }
    public function faqList()
    {
        try {
            $faqs = FAQ::query()
                ->where('category', 'General')
                ->get();

            return response()->json([
                'status' => 'success',
                'faqs' => $faqs,
            ]);
        } catch (\Exception $e) {
            Log::error('Page query not found' . $e->getMessage());
            $systemError = ApiResponseHelper::formatErrors(ApiResponseHelper::SYSTEM_ERROR, [ServerErrorMask::UNKNOWN_ERROR]);
            return response()->json([
                'status' => 'error',
                'message' => $systemError,
            ], 500);
        }
    }



    //data entry
    public function importTechniciansFromExcel()
    {
        $filePath = public_path('THPTech.xlsx'); // replace with your filename

        $rows = Excel::toArray([], $filePath)[0]; // get first sheet

        // Skip header row
        foreach (array_slice($rows, 1) as $row) {
            if (empty(array_filter($row))) {
                continue; // skip completely empty rows
            }
            $email = trim($row[1]);          // Email Address
            $address = trim($row[2]);        // Address


            DB::beginTransaction();

            try {
                // Create user
                $user = User::where('email', $email,)->first();

                Profile::where('user_id', $user->id)->update([
                    'address_1' => $address,
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Failed to import user: {$email}, error: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Import completed.']);
    }
}
