<?php

namespace App\Http\Requests;

class VolunteerFormRequest extends Request
{
    public function rules()
    {
        $rules = [
            'organization_name' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'meal_description' => 'required',
            'notes' => 'required',
            'open_event_id' => 'required',
            'open_event_date_time' => 'required',
            'bringing_food' => 'required',
        ];
        return $rules;
    }
}