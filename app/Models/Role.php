<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $fillable = [
        'uuid',
        'name',
        'tag'
    ];

    public function permission()
    {
        return $this->hasOne(RoleHasPermission::class, 'role_id');
    }

    public function roleAccess()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function texhubxAdminPermissions()
    {
        $permits = [
            [
                "name" => "Dashboard",
                "slug" => "dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "admin_dashboard.dashboard",
                    ],
                ]
            ],
            [
                "name" => "Profile Access",
                "slug" => "profile_access",
                "permissions" => [
                    [
                        "name" => "Show Profile",
                        "slug" => "profile_access.profile",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "profile_access.profile_update",
                    ],
                    [
                        "name" => "Change Password",
                        "slug" => "profile_access.password",
                    ],
                ]
            ],
            [
                "name" => "Work Category",
                "slug" => "work_category",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "work_category.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "work_category.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "work_category.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "work_category.update",
                    ],
                ]
            ],
            [
                "name" => "Work Sub Category",
                "slug" => "work_sub_category",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "work_sub_category.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "work_sub_category.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "work_sub_category.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "work_sub_category.update",
                    ],
                ]
            ],
            [
                "name" => "Service",
                "slug" => "service",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "service.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "service.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "service.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "service.update",
                    ],
                ]
            ],
            [
                "name" => "Service fees",
                "slug" => "service_fees",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "service_fees.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "service_fees.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "service_fees.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "service_fees.update",
                    ],
                ]
            ],
            [
                "name" => "Qualification",
                "slug" => "qualification",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "qualification.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "qualification.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "qualification.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "qualification.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "qualification.delete",
                    ],
                ]
            ],
            [
                "name" => "Qualification Sub Category",
                "slug" => "qualification_sub_cat",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "qualification_sub_cat.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "qualification_sub_cat.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "qualification_sub_cat.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "qualification_sub_cat.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "qualification_sub_cat.delete",
                    ],
                ]
            ],
            [
                "name" => "Blog",
                "slug" => "blog",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "blog.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "blog.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "blog.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "blog.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "blog.delete",
                    ],
                ]
            ],
            [
                "name" => "Testimonial",
                "slug" => "testimonial",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "testimonial.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "testimonial.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "testimonial.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "testimonial.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "testimonial.delete",
                    ],
                ]
            ],
            [
                "name" => "Slider",
                "slug" => "slider",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "slider.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "slider.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "slider.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "slider.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "slider.delete",
                    ],
                ]
            ],
            [
                "name" => "Brand",
                "slug" => "brand",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "brand.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "brand.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "brand.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "brand.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "brand.delete",
                    ],
                ]
            ],
            [
                "name" => "Quote",
                "slug" => "quote",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "quote.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "quote.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "quote.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "quote.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "quote.delete",
                    ],
                ]
            ],
            [
                "name" => "Get Work Step Details",
                "slug" => "get_work_step_details",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "get_work_step_details.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "get_work_step_details.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "get_work_step_details.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "get_work_step_details.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "get_work_step_details.delete",
                    ],
                ]
            ],
            [
                "name" => "Contact",
                "slug" => "contact",
                "permissions" => [
                    [
                        "name" => "Partner Contact List",
                        "slug" => "contact_partner.list",
                    ],
                    [
                        "name" => "Partner Contact View",
                        "slug" => "contact_partner.view",
                    ],
                    [
                        "name" => "Partner Contact Delete",
                        "slug" => "contact_partner.delete",
                    ],
                    [
                        "name" => "Contact Us List",
                        "slug" => "contact_us.list",
                    ],
                    [
                        "name" => "Contact Us View",
                        "slug" => "contact_us.view",
                    ],
                    [
                        "name" => "Contact Us Delete",
                        "slug" => "contact_us.delete",
                    ],
                ]
            ],
            [
                "name" => "Service Category",
                "slug" => "service_category",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "service_category.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "service_category.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "service_category.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "service_category.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "service_category.delete",
                    ],
                ]
            ],
            [
                "name" => "Frontend Service",
                "slug" => "frontend_service",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "frontend_service.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "frontend_service.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "frontend_service.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "frontend_service.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "frontend_service.delete",
                    ],
                ]
            ],
            [
                "name" => "Team",
                "slug" => "team",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "team.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "team.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "team.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "team.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "team.delete",
                    ],
                ]
            ],
            [
                "name" => "Pages",
                "slug" => "pages",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "pages.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "pages.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "pages.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "pages.update",
                    ],
                ]
            ],
            [
                "name" => "Page Header",
                "slug" => "page_header",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "page_header.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "page_header.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "page_header.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "page_header.update",
                    ],
                ]
            ],
            [
                "name" => "Page Paragraphs",
                "slug" => "page_paragraphs",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "page_paragraphs.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "page_paragraphs.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "page_paragraphs.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "page_paragraphs.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "page_paragraphs.delete",
                    ],
                ]
            ],
            [
                "name" => "FAQ",
                "slug" => "faqs",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "faqs.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "faqs.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "faqs.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "faqs.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "faqs.delete",
                    ],
                ]
            ],
            [
                "name" => "How It Works Steps",
                "slug" => "how_it_works_steps",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "how_it_works_steps.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "how_it_works_steps.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "how_it_works_steps.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "how_it_works_steps.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "how_it_works_steps.delete",
                    ],
                ]
            ],
            [
                "name" => "Plan",
                "slug" => "plan",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "plan.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "plan.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "plan.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "plan.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "plan.delete",
                    ],
                ]
            ],
            [
                "name" => "Country",
                "slug" => "country",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "country.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "country.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "country.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "country.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "country.delete",
                    ],
                ]
            ],
            [
                "name" => "State",
                "slug" => "state",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "state.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "state.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "state.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "state.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "state.delete",
                    ],
                ]
            ],
            [
                "name" => "Legal",
                "slug" => "legal",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "legal.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "legal.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "legal.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "legal.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "legal.delete",
                    ],
                ]
            ],
            [
                "name" => "Expense Category",
                "slug" => "expense_category",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "expense_category.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "expense_category.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "expense_category.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "expense_category.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "expense_category.delete",
                    ],
                ]
            ],
            [
                "name" => "Supports",
                "slug" => "supports",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "supports.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "supports.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "supports.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "supports.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "supports.delete",
                    ],
                ]
            ],
            [
                "name" => "Others",
                "slug" => "others",
                "permissions" => [
                    [
                        "name" => "Get Subscription Details",
                        "slug" => "others.get_subscription_details",
                    ],
                    [
                        "name" => "Get Client Payment Details",
                        "slug" => "others.get_client_payment_details",
                    ],
                    [
                        "name" => "Get Provider Payment Details",
                        "slug" => "others.get_provider_payment_details",
                    ],
                    [
                        "name" => "Get Client Point Request",
                        "slug" => "others.get_client_point_request",
                    ],
                    [
                        "name" => "Update Client Point Request",
                        "slug" => "others.update_client_point_request",
                    ],
                    [
                        "name" => "Get Feedback",
                        "slug" => "others.get_feedback",
                    ],
                ]
            ],
            [
                "name" => "Admin Staff",
                "slug" => "admin_staff",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "admin_staff.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "admin_staff.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "admin_staff.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "admin_staff.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "admin_staff.delete",
                    ],
                ]
            ],
            [
                "name" => "Documentation",
                "slug" => "documentations",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "documentations.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "documentations.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "documentations.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "documentations.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "documentations.delete",
                    ],
                ]
            ],
            [
                "name" => "Role & Permission Access",
                "slug" => "role_and_permission_access",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "role_and_permission_access.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "role_and_permission_access.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "role_and_permission_access.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "role_and_permission_access.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "role_and_permission_access.delete",
                    ],
                    [
                        "name" => "Permission List",
                        "slug" => "role_and_permission_access.permission_list",
                    ],
                    [
                        "name" => "My Permission",
                        "slug" => "role_and_permission_access.my_permission",
                    ],
                ]
            ],
        ];

        $data = collect($permits)->map(function ($item) {
            $item["permissions"] = collect($item["permissions"])->map(function ($permission) {
                return (object) $permission;
            });
            return (object) $item;
        });
        return $data;
    }

    public function texhubxClientPermissions()
    {
        $permits = [
            [
                "name" => "Dashboard",
                "slug" => "client_dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "client_dashboard.dashboard",
                    ],
                    [
                        "name" => "Work Order History",
                        "slug" => "client_dashboard.work_order_history",
                    ],
                    [
                        "name" => "Graph Data",
                        "slug" => "client_dashboard.graph_data",
                    ],
                ]
            ],
            [
                "name" => "Location",
                "slug" => "location",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "location.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "location.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "location.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "location.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "location.delete",
                    ],
                    [
                        "name" => "Download location",
                        "slug" => "location.download",
                    ]
                ]
            ],
            [
                "name" => "Project",
                "slug" => "project",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "project.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "project.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "project.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "project.update",
                    ],
                    [
                        "name" => "Find Project",
                        "slug" => "project.find",
                    ]
                ]

            ],
            [
                "name" => "Template",
                "slug" => "template",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "template.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "template.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "template.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "template.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "template.delete",
                    ],
                    [
                        "name" => "Find Template",
                        "slug" => "template.find",
                    ]
                ]

            ],
            [
                "name" => "Default Client",
                "slug" => "default_client",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "default_client.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "default_client.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "default_client.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "default_client.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "default_client.delete",
                    ],
                    [
                        "name" => "Import Default",
                        "slug" => "default_client.import",
                    ],
                    [
                        "name" => "Download Default Client",
                        "slug" => "default_client.download",
                    ]
                ]
            ],
            [
                "name" => "Work Order Manage",
                "slug" => "work_order_manage",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "work_order_manage.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "work_order_manage.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "work_order_manage.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "work_order_manage.update",
                    ]
                ]
            ],
            [
                "name" => "Additional Contact",
                "slug" => "additional_contact",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "additional_contact.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "additional_contact.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "additional_contact.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "additional_contact.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "additional_contact.delete",
                    ],
                ]
            ],
            [
                "name" => "Work Order",
                "slug" => "work_order",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "work_order.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "work_order.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "work_order.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "work_order.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "work_order.delete",
                    ],
                    [
                        "name" => "Work Order Status Update",
                        "slug" => "work_order.status_update",
                    ],
                    [
                        "name" => "Additional Locations",
                        "slug" => "work_order.get_additional_locations",
                    ],
                    [
                        "name" => "Work Order Get Document",
                        "slug" => "work_order.get_documents",
                    ],
                    [
                        "name" => "Timezone",
                        "slug" => "work_order.get_timezone",
                    ],
                    [
                        "name" => "Counter Offer List",
                        "slug" => "work_order.counter_offer_list",
                    ],
                    [
                        "name" => "Counter Offer View",
                        "slug" => "work_order.counter_offer_view",
                    ],
                    [
                        "name" => "Counter Offer Assigned",
                        "slug" => "work_order.assigned_counter_offer",
                    ],
                    [
                        "name" => "Work Request List",
                        "slug" => "work_order.request_list",
                    ],
                    [
                        "name" => "Work Request View",
                        "slug" => "work_order.request_view",
                    ],
                    [
                        "name" => "Work Request Assigned",
                        "slug" => "work_order.assigned_request",
                    ],
                    [
                        "name" => "Work Expense Request List",
                        "slug" => "work_order.expense_request_list",
                    ],
                    [
                        "name" => "Work Expense Request View",
                        "slug" => "work_order.expense_request_view",
                    ],
                    [
                        "name" => "Work Expense Request Approve",
                        "slug" => "work_order.expense_approve_request",
                    ],
                    [
                        "name" => "Work Pay Change List",
                        "slug" => "work_order.pay_change_list",
                    ],
                    [
                        "name" => "Work Pay Change View",
                        "slug" => "work_order.pay_change_view",
                    ],
                    [
                        "name" => "Work Pay Change Approve",
                        "slug" => "work_order.pay_change_approve",
                    ],
                    [
                        "name" => "Client Work Order Report Problem",
                        "slug" => "work_order.client_work_order_report_problem",
                    ],
                ]
            ],
            [
                "name" => "Talent",
                "slug" => "talent",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "talent.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "talent.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "talent.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "talent.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "talent.delete",
                    ],
                ]
            ],
            [
                "name" => "Pool Details",
                "slug" => "pool_details",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "pool_details.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "pool_details.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "pool_details.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "pool_details.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "pool_details.delete",
                    ],
                    [
                        "name" => "Talent Wise Provider",
                        "slug" => "pool_details.get_talent_wise_provider",
                    ],
                ]
            ],
            [
                "name" => "Subscription",
                "slug" => "subscription",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "subscription.list",
                    ],
                    [
                        "name" => "Request For Point",
                        "slug" => "subscription.request-for-point",
                    ],
                    [
                        "name" => "Get Subscription",
                        "slug" => "subscription.get_subscription",
                    ],
                    [
                        "name" => "Point Balance",
                        "slug" => "subscription.point_balance",
                    ],
                    [
                        "name" => "Cancel Subscription",
                        "slug" => "subscription.cancel_subscription",
                    ],
                ]
            ],
            [
                "name" => "Employee Provider License Certificates",
                "slug" => "employee_provider_license_certificates",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "employee_provider_license_certificates.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "employee_provider_license_certificates.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "employee_provider_license_certificates.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "employee_provider_license_certificates.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "employee_provider_license_certificates.destroy",
                    ],
                ]
            ],
            [
                "name" => "Work Order Review, Complete & Payment",
                "slug" => "work_order_r_c_p",
                "permissions" => [
                    [
                        "name" => "Client Review",
                        "slug" => "work_order_r_c_p.client_review",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "work_order_r_c_p.complete_file",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "work_order_r_c_p.work_order_payment",
                    ],
                ]
            ],
            [
                "name" => "Profile Dashboard",
                "slug" => "profile_dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "profile_dashboard.dashboard",
                    ],
                    [
                        "name" => "Logout",
                        "slug" => "profile_dashboard.logout",
                    ],
                ]
            ],
            [
                "name" => "Providers",
                "slug" => "providers",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "providers.list",
                    ],
                    [
                        "name" => "Details",
                        "slug" => "providers.details",
                    ],
                ]
            ],
            [
                "name" => "Profile, Country & Company",
                "slug" => "p_c_c",
                "permissions" => [
                    [
                        "name" => "Country List",
                        "slug" => "p_c_c.list",
                    ],
                    [
                        "name" => "Company Update",
                        "slug" => "p_c_c.company_update",
                    ],
                    [
                        "name" => "Profile Update",
                        "slug" => "p_c_c.profile_update",
                    ],
                    [
                        "name" => "Username Check",
                        "slug" => "p_c_c.username_check",
                    ],
                ]
            ],
            [
                "name" => "Bank Account",
                "slug" => "bank_account",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "bank_account.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "bank_account.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "bank_account.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "bank_account.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "bank_account.delete",
                    ],
                ]
            ],
            [
                "name" => "Qualification",
                "slug" => "qualification",
                "permissions" => [
                    [
                        "name" => "Qualification Type",
                        "slug" => "qualification.type",
                    ],
                    [
                        "name" => "Qualification Wise Category",
                        "slug" => "qualification.category",
                    ],
                    [
                        "name" => "Qualification For License Certificate",
                        "slug" => "qualification.license_certificate",
                    ],
                ]
            ],
            [
                "name" => "Profile Access",
                "slug" => "profile_access",
                "permissions" => [
                    [
                        "name" => "Change Email",
                        "slug" => "profile_access.change_email",
                    ],
                    [
                        "name" => "Account Deletion",
                        "slug" => "profile_access.account_deletion",
                    ],
                ]
            ],
            [
                "name" => "Work Order & Service",
                "slug" => "work_service_order",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_service_order.view_category",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_service_order.view_service",
                    ],
                ]
            ],
            [
                "name" => "Work Order Access",
                "slug" => "work_order_access",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_order_access.list",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.details",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.review",
                    ],
                ]
            ],
            [
                "name" => "Notification, Chat & Messaging",
                "slug" => "chat_and_messaging",
                "permissions" => [
                    [
                        "name" => "Messages User List",
                        "slug" => "chat_and_messaging.user_list",
                    ],
                    [
                        "name" => "Change Assign Provider",
                        "slug" => "chat_and_messaging.assign_provider",
                    ],
                    [
                        "name" => "Show Work Order Wise Chat Messages",
                        "slug" => "chat_and_messaging.work_order_wise_message",
                    ],
                    [
                        "name" => "Send Message",
                        "slug" => "chat_and_messaging.send_message",
                    ],
                    [
                        "name" => "Chat List",
                        "slug" => "chat_and_messaging.chat_list",
                    ],
                    [
                        "name" => "Get Notification",
                        "slug" => "chat_and_messaging.notification_list",
                    ],
                ]
            ],
            [
                "name" => "Subscription , Plan & Feedback",
                "slug" => "subscription_p_f",
                "permissions" => [
                    [
                        "name" => "Subscription Payment",
                        "slug" => "subscription_p_f.subscription_payment",
                    ],
                    [
                        "name" => "Plan Details",
                        "slug" => "subscription_p_f.plan_details",
                    ],
                    [
                        "name" => "Feedback",
                        "slug" => "subscription_p_f.feedback",
                    ],
                ]
            ],
            [
                "name" => "Role & Permission Access",
                "slug" => "role_and_permission_access",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "role_and_permission_access.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "role_and_permission_access.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "role_and_permission_access.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "role_and_permission_access.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "role_and_permission_access.delete",
                    ],
                    [
                        "name" => "Permission List",
                        "slug" => "role_and_permission_access.permission_list",
                    ],
                    [
                        "name" => "My Permission",
                        "slug" => "role_and_permission_access.my_permission",
                    ],
                ]
            ],
        ];

        $data = collect($permits)->map(function ($item) {
            $item["permissions"] = collect($item["permissions"])->map(function ($permission) {
                return (object) $permission;
            });
            return (object) $item;
        });
        return $data;
    }

    public function texhubxProviderPermissions()
    {
        $permits = [
            [
                "name" => "Dashboard",
                "slug" => "provider_dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "provider_dashboard.dashboard",
                    ],
                ]
            ],
            [
                "name" => "Work Order Checkout",
                "slug" => "work_order_checkout",
                "permissions" => [
                    [
                        "name" => "Work Order Start Time",
                        "slug" => "work_order_checkout.start_time",
                    ],
                    [
                        "name" => "Confirm Work Order",
                        "slug" => "work_order_checkout.confirm_work_order",
                    ],
                    [
                        "name" => "Mark On My Way",
                        "slug" => "work_order_checkout.mark_on_my_way",
                    ],
                    [
                        "name" => "Check In",
                        "slug" => "work_order_checkout.check_in",
                    ],
                ]
            ],
            [
                "name" => "Employee Providers",
                "slug" => "employee_providers",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "employee_providers.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "employee_providers.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "employee_providers.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "employee_providers.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "employee_providers.delete",
                    ],
                ]
            ],
            [
                "name" => "Send Work Requests",
                "slug" => "send_work_requests",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "send_work_requests.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "send_work_requests.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "send_work_requests.Edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "send_work_requests.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "send_work_requests.delete",
                    ],
                ]
            ],
            [
                "name" => "Counter Offer",
                "slug" => "counter_offer",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "counter_offer.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "counter_offer.create_store",
                    ],
                ]
            ],
            [
                "name" => "Expense Request",
                "slug" => "expense_request",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "expense_request.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "expense_request.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "expense_request.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "expense_request.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "expense_request.delete",
                    ],
                    [
                        "name" => "Work Order Wise Expense Request",
                        "slug" => "expense_request.work_wise_expense_request",
                    ],
                ]
            ],
            [
                "name" => "Pay Change Request",
                "slug" => "pay_change_request",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "pay_change_request.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "pay_change_request.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "pay_change_request.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "pay_change_request.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "pay_change_request.delete",
                    ],
                    [
                        "name" => "Work Order Wise Pay Change Request",
                        "slug" => "pay_change_request.work_wise_pay_change_request",
                    ],
                ]
            ],
            [
                "name" => "Provider License & Certificate details",
                "slug" => "provider_license_certificate_details",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "provider_license_certificate_details.list",
                    ],
                    [
                        "name" => "Check",
                        "slug" => "provider_license_certificate_details.check",
                    ],
                ]
            ],
            [
                "name" => "Provider Work Order",
                "slug" => "provider_work_order",
                "permissions" => [
                    [
                        "name" => "Work Report",
                        "slug" => "provider_work_order.list",
                    ],
                    [
                        "name" => "Work Order Review",
                        "slug" => "provider_work_order.review",
                    ],
                    [
                        "name" => "Work Order Complete",
                        "slug" => "provider_work_order.complete",
                    ],
                    [
                        "name" => "Work Order Payment details",
                        "slug" => "provider_work_order.details",
                    ],
                    [
                        "name" => "Work Order Sent Report Problem",
                        "slug" => "provider_work_order.report_problem",
                    ],
                    [
                        "name" => "Work Order Interest",
                        "slug" => "provider_work_order.interest",
                    ],
                ]
            ],
            [
                "name" => "Profile Dashboard",
                "slug" => "profile_dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "profile_dashboard.dashboard",
                    ],
                    [
                        "name" => "Logout",
                        "slug" => "profile_dashboard.logout",
                    ],
                ]
            ],
            [
                "name" => "Providers",
                "slug" => "providers",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "providers.list",
                    ],
                    [
                        "name" => "Details",
                        "slug" => "providers.details",
                    ],
                ]
            ],
            [
                "name" => "Profile, Country & Company",
                "slug" => "p_c_c",
                "permissions" => [
                    [
                        "name" => "Country List",
                        "slug" => "p_c_c.list",
                    ],
                    [
                        "name" => "Company Update",
                        "slug" => "p_c_c.company_update",
                    ],
                    [
                        "name" => "Profile Update",
                        "slug" => "p_c_c.profile_update",
                    ],
                    [
                        "name" => "Username Check",
                        "slug" => "p_c_c.username_check",
                    ],
                ]
            ],
            [
                "name" => "Bank Account",
                "slug" => "bank_account",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "bank_account.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "bank_account.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "bank_account.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "bank_account.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "bank_account.delete",
                    ],
                ]
            ],
            [
                "name" => "Qualification",
                "slug" => "qualification",
                "permissions" => [
                    [
                        "name" => "Qualification Type",
                        "slug" => "qualification.type",
                    ],
                    [
                        "name" => "Qualification Wise Category",
                        "slug" => "qualification.category",
                    ],
                    [
                        "name" => "Qualification For License Certificate",
                        "slug" => "qualification.license_certificate",
                    ],
                ]
            ],
            [
                "name" => "Profile Access",
                "slug" => "profile_access",
                "permissions" => [
                    [
                        "name" => "Change Email",
                        "slug" => "profile_access.change_email",
                    ],
                    [
                        "name" => "Account Deletion",
                        "slug" => "profile_access.account_deletion",
                    ],
                ]
            ],
            [
                "name" => "Work Order & Service",
                "slug" => "work_service_order",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_service_order.view_category",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_service_order.view_service",
                    ],
                ]
            ],
            [
                "name" => "Work Order Access",
                "slug" => "work_order_access",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_order_access.list",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.details",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.review",
                    ],
                ]
            ],
            [
                "name" => "Notification, Chat & Messaging",
                "slug" => "chat_and_messaging",
                "permissions" => [
                    [
                        "name" => "Messages User List",
                        "slug" => "chat_and_messaging.user_list",
                    ],
                    [
                        "name" => "Change Assign Provider",
                        "slug" => "chat_and_messaging.assign_provider",
                    ],
                    [
                        "name" => "Show Work Order Wise Chat Messages",
                        "slug" => "chat_and_messaging.work_order_wise_message",
                    ],
                    [
                        "name" => "Send Message",
                        "slug" => "chat_and_messaging.send_message",
                    ],
                    [
                        "name" => "Chat List",
                        "slug" => "chat_and_messaging.chat_list",
                    ],
                    [
                        "name" => "Get Notification",
                        "slug" => "chat_and_messaging.notification_list",
                    ],
                ]
            ],
            [
                "name" => "Subscription , Plan & Feedback",
                "slug" => "subscription_p_f",
                "permissions" => [
                    [
                        "name" => "Subscription Payment",
                        "slug" => "subscription_p_f.subscription_payment",
                    ],
                    [
                        "name" => "Plan Details",
                        "slug" => "subscription_p_f.plan_details",
                    ],
                    [
                        "name" => "Feedback",
                        "slug" => "subscription_p_f.feedback",
                    ],
                ]
            ],
            [
                "name" => "Role & Permission Access",
                "slug" => "role_and_permission_access",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "role_and_permission_access.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "role_and_permission_access.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "role_and_permission_access.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "role_and_permission_access.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "role_and_permission_access.delete",
                    ],
                    [
                        "name" => "Permission List",
                        "slug" => "role_and_permission_access.permission_list",
                    ],
                    [
                        "name" => "My Permission",
                        "slug" => "role_and_permission_access.my_permission",
                    ],
                ]
            ],
        ];

        $data = collect($permits)->map(function ($item) {
            $item["permissions"] = collect($item["permissions"])->map(function ($permission) {
                return (object) $permission;
            });
            return (object) $item;
        });
        return $data;
    }

    public function texhubxCommonPermissions()
    {
        $permits = [
            [
                "name" => "Profile Dashboard",
                "slug" => "profile_dashboard",
                "permissions" => [
                    [
                        "name" => "Dashboard",
                        "slug" => "profile_dashboard.dashboard",
                    ],
                    [
                        "name" => "Logout",
                        "slug" => "profile_dashboard.logout",
                    ],
                ]
            ],
            [
                "name" => "Providers",
                "slug" => "providers",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "providers.list",
                    ],
                    [
                        "name" => "Details",
                        "slug" => "providers.details",
                    ],
                ]
            ],
            [
                "name" => "Profile, Country & Company",
                "slug" => "p_c_c",
                "permissions" => [
                    [
                        "name" => "Country List",
                        "slug" => "p_c_c.list",
                    ],
                    [
                        "name" => "Company Update",
                        "slug" => "p_c_c.company_update",
                    ],
                    [
                        "name" => "Profile Update",
                        "slug" => "p_c_c.profile_update",
                    ],
                    [
                        "name" => "Username Check",
                        "slug" => "p_c_c.username_check",
                    ],
                ]
            ],
            [
                "name" => "Bank Account",
                "slug" => "bank_account",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "bank_account.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "bank_account.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "bank_account.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "bank_account.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "bank_account.delete",
                    ],
                ]
            ],
            [
                "name" => "Qualification",
                "slug" => "qualification",
                "permissions" => [
                    [
                        "name" => "Qualification Type",
                        "slug" => "qualification.type",
                    ],
                    [
                        "name" => "Qualification Wise Category",
                        "slug" => "qualification.category",
                    ],
                    [
                        "name" => "Qualification For License Certificate",
                        "slug" => "qualification.license_certificate",
                    ],
                ]
            ],
            [
                "name" => "Profile Access",
                "slug" => "profile_access",
                "permissions" => [
                    [
                        "name" => "Change Email",
                        "slug" => "profile_access.change_email",
                    ],
                    [
                        "name" => "Account Deletion",
                        "slug" => "profile_access.account_deletion",
                    ],
                ]
            ],
            [
                "name" => "Work Order & Service",
                "slug" => "work_service_order",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_service_order.view_category",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_service_order.view_service",
                    ],
                ]
            ],
            [
                "name" => "Work Order Access",
                "slug" => "work_order_access",
                "permissions" => [
                    [
                        "name" => "Work Category",
                        "slug" => "work_order_access.list",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.details",
                    ],
                    [
                        "name" => "Service",
                        "slug" => "work_order_access.review",
                    ],
                ]
            ],
            [
                "name" => "Notification, Chat & Messaging",
                "slug" => "chat_and_messaging",
                "permissions" => [
                    [
                        "name" => "Messages User List",
                        "slug" => "chat_and_messaging.user_list",
                    ],
                    [
                        "name" => "Change Assign Provider",
                        "slug" => "chat_and_messaging.assign_provider",
                    ],
                    [
                        "name" => "Show Work Order Wise Chat Messages",
                        "slug" => "chat_and_messaging.work_order_wise_message",
                    ],
                    [
                        "name" => "Send Message",
                        "slug" => "chat_and_messaging.send_message",
                    ],
                    [
                        "name" => "Chat List",
                        "slug" => "chat_and_messaging.chat_list",
                    ],
                    [
                        "name" => "Get Notification",
                        "slug" => "chat_and_messaging.notification_list",
                    ],
                ]
            ],
            [
                "name" => "Subscription , Plan & Feedback",
                "slug" => "subscription_p_f",
                "permissions" => [
                    [
                        "name" => "Subscription Payment",
                        "slug" => "subscription_p_f.subscription_payment",
                    ],
                    [
                        "name" => "Plan Details",
                        "slug" => "subscription_p_f.plan_details",
                    ],
                    [
                        "name" => "Feedback",
                        "slug" => "subscription_p_f.feedback",
                    ],
                ]
            ],
            [
                "name" => "Role & Permission Access",
                "slug" => "role_and_permission_access",
                "permissions" => [
                    [
                        "name" => "List",
                        "slug" => "role_and_permission_access.list",
                    ],
                    [
                        "name" => "Create & Store",
                        "slug" => "role_and_permission_access.create_store",
                    ],
                    [
                        "name" => "Edit",
                        "slug" => "role_and_permission_access.edit",
                    ],
                    [
                        "name" => "Update",
                        "slug" => "role_and_permission_access.update",
                    ],
                    [
                        "name" => "Delete",
                        "slug" => "role_and_permission_access.delete",
                    ],
                    [
                        "name" => "Permission List",
                        "slug" => "role_and_permission_access.permission_list",
                    ],
                    [
                        "name" => "My Permission",
                        "slug" => "role_and_permission_access.my_permission",
                    ],
                ]
            ],
        ];

        $data = collect($permits)->map(function ($item) {
            $item["permissions"] = collect($item["permissions"])->map(function ($permission) {
                return (object) $permission;
            });
            return (object) $item;
        });
        return $data;
    }
}
