<?php

namespace App\Utils;

class GlobalConstant
{
    const LOCATION_TYPE = ['Commercial', 'Government', 'Residential', 'Educational', 'Other'];
    const ORGANIZATION = ['Client', 'Provider', 'Provider Company', 'Main'];
    const SWITCH = ['Active', 'Inactive'];
    const ORDER_SWITCH = ['Requested', 'Pending', 'Competed', 'Declined', 'Assigned', 'Routed', 'Counter', 'Expired'];
    const STATUS = ['Active', 'Hidden'];
    const GENDER = ['Male', 'Female', 'Other'];
    const AD = ['Pending', 'Accept ', 'Declined'];
    const YN = ['Yes', 'No'];
    const REVENUE = ['Less Than $10M', '$10M-$24M', '$25M-$499M', '$500M', '$1000M+'];
    const NEED = ["Daily", "Weekly", "Monthly", "Occasionally", "I don\'t currently use technicians"];
    const EMP_COUNTER = ['1-10', '11-24', '25-50', '51-99', '100-499', '500-999', '1000-2000', '2000+'];
    const HIRE = ['Today', 'This Week', 'This Month', 'This Quarter', 'This Year', 'Next Year Or Later'];
    const WITHDRAW = ['15 Minutes', '30 Minutes', '1 Hour', '2 Hours', '3 Hours', '6 Hours', '12 Hours', '1 Days', '2 Days'];
    const PAY_TYPE = ['Hourly', 'Fixed', 'Per Device', 'Blended'];
    const SCHEDULES_TYPE = ['Range', 'Arrive at a specific date and time'];
    const EXPENSES_CATEGORY = ['Freight', 'Personal Material Costs (Other Materials)', 'Real Material Costs (attached to land/building)', 'Scope-of-Work Change', 'Taxes', 'Travel', 'Travel Expense'];
    const PARTNERSHIP_INTERESTED = ['Platform Management', 'Technology', 'Referral', 'Other'];
    const PAGE_SWITCH = ['About Us', 'Contact Us', 'Terms of Service', 'Privacy & Policy', 'Non-discrimination policy', 'Other'];
    const PAYMENT_METHOD = ['Online', 'Other'];
    const ACCOUNT_TYPE = ['Business', 'Credit', 'Salary', 'Others'];
    const ORDER_SCHEDULE_TYPE = ['Arrive at a specific date and time - (Hard Start)', 'Complete work between specific hours', 'Complete work anytime over a date range'];
    const ORDER_STATUS = ['Active', 'Complete', 'Cancelled', 'Inactive', 'Published', 'Assigned', 'Done', 'Approved', 'Draft', 'In-Flight'];
    const ORDER_ASSIGNED_STATUS = ['Alert', 'Review', 'On-track', 'On hold', 'Assigned', 'Start time set', 'Checked in'];
    const ADDITIONAL_TITLE = ['Project manager', 'Location Contact', 'Resource coordinator', 'Emergency contact', 'Technical help', 'Check-in / Check-out'];
    const WORK_ORDER_ASSIGNED_SCHEDULE_TYPE = ['Specific date and time', 'Complete work between'];
    const WORK_ORDER_TITLE = ['Project', 'Manager'];
    const TRANSACTION_TYPE = ['Payment', 'Withdraw', 'Add Money', 'Charge', 'Refund', 'Cancel', 'Subscription', 'Point'];
    const TRANSACTION_SWITCH = ['Pending', 'Hold', 'Completed', 'Settlement', 'Reject', 'Deposited', 'Under Review', 'Cancelled'];
    const PAYMENT_STATUS = ['Pending', 'Hold', 'Complete', 'Settlement', 'Reject', 'Deposited', 'Refunded', 'Cancelled'];
    const PAYMENT_GATEWAY = ['PayPal', 'Stripe'];
    const POINT_TRANSACTION_SWITCH = ['Add Point', 'Send Point', 'Complete', 'Pending'];
    const SUBSCRIPTION_SWITCH = ['Active', 'Inactive', 'Payment', 'Pending', 'Complete', 'Cancelled', 'Under Review'];
    const REVIEW_STAR = ['1', '2', '3', '4', '5'];
    const FAQ_CATEGORY = ["Provider", "Client", "General"];
}
