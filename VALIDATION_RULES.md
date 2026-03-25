# Validation Rules

> **AI IMPLEMENTATION INSTRUCTION:** Implement all validation in `src/Helpers/Validator.php`. Every controller MUST validate input before passing to service layer. Return `422 VALIDATION_ERROR` on failure.

## Validator Usage Pattern

```php
// In any controller method:
$validator = new Validator($request->body());
$validator->rules([
    'email' => 'required|email|max:255',
    'name'  => 'required|string|min:2|max:100',
]);
if ($validator->fails()) {
    return Response::json(422, [
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'The given data was invalid.',
            'details' => $validator->errors()
        ]
    ]);
}
```

## Rule Reference

| Rule | Syntax | Example |
|------|--------|---------|
| Required | `required` | Field must be present and non-empty |
| String | `string` | Must be a string |
| Integer | `integer` | Must be a whole number |
| Numeric | `numeric` | Must be numeric (int or float) |
| Email | `email` | Must be valid email via `filter_var` |
| Min length | `min:N` | String min N characters, number min value N |
| Max length | `max:N` | String max N characters, number max value N |
| Between | `between:N,M` | Value between N and M |
| In list | `in:a,b,c` | Must be one of the listed values |
| Date | `date` | Must be valid `Y-m-d` or `Y-m-d H:i:s` |
| Date after | `after:field` | Must be after another date field |
| Date before | `before:field` | Must be before another date field |
| File | `file` | Must be an uploaded file |
| File max size | `file_max:N` | Max N kilobytes |
| File type | `file_types:jpg,png,pdf` | Allowed MIME extensions |
| Unique | `unique:table,column` | Must not exist in DB |
| Exists | `exists:table,column` | Must exist in DB |
| Confirmed | `confirmed` | Must match `{field}_confirmation` |
| Nullable | `nullable` | Field can be null/empty (skips other rules if so) |
| Array | `array` | Must be an array |
| Boolean | `boolean` | Must be true/false/1/0 |
| Regex | `regex:/pattern/` | Must match regex |
| Phone (PH) | `phone_ph` | Custom: matches `+63` or `09` PH format |

---

## 1. Authentication

### Login (`POST /api/auth/login`)
| Field | Rules | Notes |
|-------|-------|-------|
| `email` | `required\|email\|max:255` | |
| `password` | `required\|string\|min:8` | |

### Register — Adopter (`POST /api/adopt/register`)
| Field | Rules | Notes |
|-------|-------|-------|
| `email` | `required\|email\|max:255\|unique:users,email` | |
| `password` | `required\|string\|min:8\|confirmed` | Must contain uppercase, lowercase, digit |
| `password_confirmation` | `required\|string` | Must match password |
| `first_name` | `required\|string\|min:2\|max:100` | |
| `last_name` | `required\|string\|min:2\|max:100` | |
| `middle_name` | `nullable\|string\|max:100` | |
| `phone` | `required\|phone_ph` | `+639XXXXXXXXX` or `09XXXXXXXXX` |
| `address_line1` | `required\|string\|max:255` | |
| `city` | `required\|string\|max:100` | |
| `province` | `required\|string\|max:100` | |
| `zip_code` | `required\|string\|max:10` | |

### Forgot Password (`POST /api/auth/forgot-password`)
| Field | Rules |
|-------|-------|
| `email` | `required\|email\|exists:users,email` |

### Reset Password (`POST /api/auth/reset-password`)
| Field | Rules |
|-------|-------|
| `token` | `required\|string` |
| `password` | `required\|string\|min:8\|confirmed` |
| `password_confirmation` | `required\|string` |

### Change Password (`PUT /api/auth/change-password`)
| Field | Rules |
|-------|-------|
| `current_password` | `required\|string` |
| `new_password` | `required\|string\|min:8\|confirmed` |
| `new_password_confirmation` | `required\|string` |

---

## 2. Animals

### Create Animal (`POST /api/animals`)
| Field | Rules | Notes |
|-------|-------|-------|
| `name` | `nullable\|string\|max:100` | May be unnamed |
| `species` | `required\|in:Dog,Cat,Other` | |
| `breed_id` | `nullable\|integer\|exists:breeds,id` | |
| `breed_other` | `nullable\|string\|max:100` | Required if breed is Other |
| `gender` | `required\|in:Male,Female` | |
| `age_years` | `nullable\|integer\|between:0,30` | |
| `age_months` | `nullable\|integer\|between:0,11` | |
| `color_markings` | `nullable\|string\|max:255` | |
| `size` | `required\|in:Small,Medium,Large,Extra Large` | |
| `weight_kg` | `nullable\|numeric\|between:0.1,150` | |
| `distinguishing_features` | `nullable\|string\|max:1000` | |
| `intake_type` | `required\|in:Stray,Owner Surrender,Confiscated,Transfer,Born in Shelter` | |
| `intake_date` | `required\|date` | |
| `location_found` | `nullable\|string\|max:500` | Required if Stray |
| `brought_by_name` | `nullable\|string\|max:200` | |
| `brought_by_contact` | `nullable\|phone_ph` | |
| `brought_by_address` | `nullable\|string\|max:500` | |
| `surrender_reason` | `nullable\|string\|max:1000` | Required if Owner Surrender |
| `condition_at_intake` | `required\|in:Healthy,Injured,Sick,Malnourished,Aggressive` | |
| `temperament` | `required\|in:Friendly,Shy,Aggressive,Unknown` | Default: Unknown |
| `kennel_id` | `nullable\|integer\|exists:kennels,id` | Must be available kennel |
| `photos[]` | `nullable\|array\|max:5` | |
| `photos[].*` | `file\|file_max:5120\|file_types:jpg,jpeg,png,webp` | Max 5MB each |

### Update Animal Status (`PUT /api/animals/{id}/status`)
| Field | Rules |
|-------|-------|
| `status` | `required\|in:Available,Under Medical Care,In Adoption Process,Adopted,Deceased,Transferred,Quarantine` |
| `status_reason` | `nullable\|string\|max:500` |

---

## 3. Kennels

### Create Kennel (`POST /api/kennels`)
| Field | Rules |
|-------|-------|
| `kennel_code` | `required\|string\|max:20\|unique:kennels,kennel_code` |
| `zone` | `required\|string\|max:50` |
| `row_number` | `nullable\|string\|max:10` |
| `size_category` | `required\|in:Small,Medium,Large,Extra Large` |
| `type` | `required\|in:Indoor,Outdoor` |
| `allowed_species` | `required\|in:Dog,Cat,Any` |
| `max_occupants` | `required\|integer\|between:1,10` |

### Assign Animal (`POST /api/kennels/{id}/assign`)
| Field | Rules | Notes |
|-------|-------|-------|
| `animal_id` | `required\|integer\|exists:animals,id` | Animal must not be already assigned |

---

## 4. Medical Records

### Common Fields (all procedure types)
| Field | Rules |
|-------|-------|
| `animal_id` | `required\|integer\|exists:animals,id` |
| `record_date` | `required\|date` |
| `veterinarian_id` | `required\|integer\|exists:users,id` |
| `general_notes` | `nullable\|string\|max:2000` |

### Vaccination (`POST /api/medical/vaccination`)
| Field | Rules |
|-------|-------|
| `vaccine_name` | `required\|string\|max:100` |
| `vaccine_brand` | `nullable\|string\|max:100` |
| `batch_lot_number` | `nullable\|string\|max:50` |
| `dosage_ml` | `required\|numeric\|between:0.01,100` |
| `route` | `required\|in:Subcutaneous,Intramuscular,Oral` |
| `injection_site` | `nullable\|string\|max:50` |
| `dose_number` | `required\|integer\|between:1,10` |
| `next_due_date` | `nullable\|date\|after:record_date` |
| `adverse_reactions` | `nullable\|string\|max:1000` |

### Surgery (`POST /api/medical/surgery`)
| Field | Rules |
|-------|-------|
| `surgery_type` | `required\|in:Spay,Neuter,Tumor Removal,Amputation,Wound Repair,Other` |
| `pre_op_weight_kg` | `nullable\|numeric\|between:0.1,150` |
| `anesthesia_type` | `required\|in:General,Local,Sedation` |
| `anesthesia_drug` | `nullable\|string\|max:100` |
| `anesthesia_dosage` | `nullable\|string\|max:50` |
| `duration_minutes` | `nullable\|integer\|between:1,1440` |
| `surgical_notes` | `nullable\|string\|max:2000` |
| `complications` | `nullable\|string\|max:1000` |
| `post_op_instructions` | `nullable\|string\|max:2000` |
| `follow_up_date` | `nullable\|date\|after:record_date` |

### Examination (`POST /api/medical/examination`)
| Field | Rules |
|-------|-------|
| `weight_kg` | `nullable\|numeric\|between:0.1,150` |
| `temperature_celsius` | `nullable\|numeric\|between:35,43` |
| `heart_rate_bpm` | `nullable\|integer\|between:30,300` |
| `respiratory_rate` | `nullable\|integer\|between:5,100` |
| `body_condition_score` | `nullable\|integer\|between:1,9` |
| `eyes_status` | `nullable\|in:Normal,Abnormal` |
| `ears_status` | `nullable\|in:Normal,Abnormal` |
| `teeth_gums_status` | `nullable\|in:Normal,Abnormal` |
| `skin_coat_status` | `nullable\|in:Normal,Abnormal` |
| `musculoskeletal_status` | `nullable\|in:Normal,Abnormal` |
| `overall_assessment` | `nullable\|string\|max:2000` |
| `recommendations` | `nullable\|string\|max:2000` |

### Treatment (`POST /api/medical/treatment`)
| Field | Rules |
|-------|-------|
| `diagnosis` | `required\|string\|max:255` |
| `medication_name` | `required\|string\|max:150` |
| `dosage` | `required\|string\|max:100` |
| `route` | `required\|in:Oral,Injection,Topical,IV` |
| `frequency` | `required\|string\|max:50` |
| `duration_days` | `nullable\|integer\|between:1,365` |
| `start_date` | `required\|date` |
| `end_date` | `nullable\|date\|after:start_date` |
| `quantity_dispensed` | `nullable\|integer\|between:1,1000` |
| `inventory_item_id` | `nullable\|integer\|exists:inventory_items,id` |
| `special_instructions` | `nullable\|string\|max:1000` |

### Deworming (`POST /api/medical/deworming`)
| Field | Rules |
|-------|-------|
| `dewormer_name` | `required\|string\|max:100` |
| `brand` | `nullable\|string\|max:100` |
| `dosage` | `required\|string\|max:100` |
| `weight_at_treatment_kg` | `nullable\|numeric\|between:0.1,150` |
| `next_due_date` | `nullable\|date\|after:record_date` |

### Euthanasia (`POST /api/medical/euthanasia`)
| Field | Rules |
|-------|-------|
| `reason_category` | `required\|in:Medical,Behavioral,Legal/Court Order,Population Management` |
| `reason_details` | `required\|string\|min:10\|max:2000` |
| `authorized_by` | `required\|integer\|exists:users,id` |
| `method` | `required\|string\|max:50` |
| `drug_used` | `nullable\|string\|max:100` |
| `drug_dosage` | `nullable\|string\|max:50` |
| `time_of_death` | `required\|date` |
| `disposal_method` | `required\|in:Cremation,Burial` |

---

## 5. Adoption Applications

### Submit Application (`POST /api/adopt/apply`)
| Field | Rules |
|-------|-------|
| `animal_id` | `nullable\|integer\|exists:animals,id` |
| `preferred_species` | `nullable\|in:Dog,Cat` |
| `preferred_size` | `nullable\|in:Small,Medium,Large,Extra Large` |
| `preferred_gender` | `nullable\|in:Male,Female` |
| `housing_type` | `required\|in:House,Apartment,Condo` |
| `housing_ownership` | `required\|in:Owned,Rented` |
| `has_yard` | `required\|boolean` |
| `yard_size` | `nullable\|string\|max:50` |
| `num_adults` | `required\|integer\|between:1,20` |
| `num_children` | `required\|integer\|between:0,20` |
| `children_ages` | `nullable\|string\|max:100` |
| `existing_pets_description` | `nullable\|string\|max:500` |
| `previous_pet_experience` | `nullable\|string\|max:500` |
| `vet_reference_name` | `nullable\|string\|max:200` |
| `vet_reference_clinic` | `nullable\|string\|max:200` |
| `vet_reference_contact` | `nullable\|phone_ph` |
| `valid_id_path` | `required\|file\|file_max:10240\|file_types:jpg,jpeg,png,pdf` |
| `agrees_to_policies` | `required\|boolean` |
| `agrees_to_home_visit` | `required\|boolean` |
| `agrees_to_return_policy` | `required\|boolean` |

### Schedule Interview (`POST /api/adoptions/{id}/interview`)
| Field | Rules |
|-------|-------|
| `scheduled_date` | `required\|date\|after:today` |
| `interview_type` | `required\|in:in_person,video_call` |
| `video_call_link` | `nullable\|string\|max:500` |
| `location` | `nullable\|string\|max:255` |

---

## 6. Billing

### Create Invoice (`POST /api/billing/invoices`)
| Field | Rules |
|-------|-------|
| `payor_type` | `required\|in:adopter,owner,external` |
| `payor_user_id` | `nullable\|integer\|exists:users,id` |
| `payor_name` | `required\|string\|max:200` |
| `payor_contact` | `nullable\|phone_ph` |
| `payor_address` | `nullable\|string\|max:500` |
| `animal_id` | `nullable\|integer\|exists:animals,id` |
| `application_id` | `nullable\|integer\|exists:adoption_applications,id` |
| `due_date` | `required\|date\|after:today` |
| `notes` | `nullable\|string\|max:1000` |
| `line_items` | `required\|array\|min:1` |
| `line_items.*.description` | `required\|string\|max:500` |
| `line_items.*.quantity` | `required\|integer\|between:1,999` |
| `line_items.*.unit_price` | `required\|numeric\|between:0.01,999999` |
| `line_items.*.fee_schedule_id` | `nullable\|integer\|exists:fee_schedule,id` |

### Record Payment (`POST /api/billing/invoices/{id}/payments`)
| Field | Rules |
|-------|-------|
| `amount` | `required\|numeric\|between:0.01,999999` |
| `payment_method` | `required\|in:Cash,Bank Transfer,GCash,Maya,Check` |
| `reference_number` | `nullable\|string\|max:100` |
| `payment_date` | `required\|date` |
| `notes` | `nullable\|string\|max:500` |

---

## 7. Inventory

### Create Item (`POST /api/inventory`)
| Field | Rules |
|-------|-------|
| `sku` | `required\|string\|max:50\|unique:inventory_items,sku` |
| `name` | `required\|string\|max:200` |
| `category_id` | `required\|integer\|exists:inventory_categories,id` |
| `unit_of_measure` | `required\|in:pcs,ml,mg,kg,box,pack,bottle,vial,tube,roll` |
| `cost_per_unit` | `nullable\|numeric\|between:0,999999` |
| `supplier_name` | `nullable\|string\|max:200` |
| `supplier_contact` | `nullable\|string\|max:100` |
| `reorder_level` | `required\|integer\|between:0,10000` |
| `quantity_on_hand` | `required\|integer\|between:0,99999` |
| `storage_location` | `nullable\|string\|max:100` |
| `expiry_date` | `nullable\|date` |

### Stock Transaction (`POST /api/inventory/{id}/stock-in` or `stock-out`)
| Field | Rules |
|-------|-------|
| `quantity` | `required\|integer\|between:1,10000` |
| `reason` | `required\|in:purchase,donation,return,usage,dispensed,wastage,transfer,count_correction` |
| `batch_lot_number` | `nullable\|string\|max:50` |
| `expiry_date` | `nullable\|date` |
| `source_supplier` | `nullable\|string\|max:200` |
| `notes` | `nullable\|string\|max:500` |

---

## 8. Users (Admin)

### Create User (`POST /api/users`)
| Field | Rules |
|-------|-------|
| `email` | `required\|email\|max:255\|unique:users,email` |
| `password` | `required\|string\|min:8` |
| `first_name` | `required\|string\|min:2\|max:100` |
| `last_name` | `required\|string\|min:2\|max:100` |
| `middle_name` | `nullable\|string\|max:100` |
| `phone` | `nullable\|phone_ph` |
| `role_id` | `required\|integer\|exists:roles,id` |
| `address_line1` | `nullable\|string\|max:255` |
| `city` | `nullable\|string\|max:100` |
| `province` | `nullable\|string\|max:100` |

---

## Sanitization Rules (Applied automatically before validation)

```php
// src/Helpers/Sanitizer.php — applied to ALL string inputs
public static function clean(string $input): string {
    $input = trim($input);                    // Remove whitespace
    $input = strip_tags($input);              // Remove HTML tags
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Encode special chars
    return $input;
}
```

| Input Type | Additional Sanitization |
|------------|------------------------|
| Email | `filter_var($email, FILTER_SANITIZE_EMAIL)` then `strtolower()` |
| Phone | Remove all non-digit characters except `+` |
| Currency | Cast to `float`, round to 2 decimal places |
| Integer IDs | Cast to `int` |
| File names | Remove path traversal characters (`..`, `/`, `\`) |
| Search queries | Escape SQL wildcards (`%`, `_`) for LIKE queries |
