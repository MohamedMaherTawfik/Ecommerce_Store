<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactUs;
use App\Http\Requests\ContactUs\StoreContactUsRequest;

class ContactUsController extends Controller
{
    public function store(StoreContactUsRequest $request)
    {
        $data = $request->validated();

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $data['user_id'] = $user->id;
            $data['name'] = $user->name ?? $user->first_name . ' ' . $user->last_name;
            $data['email'] = $user->email;
        }


        $contact = ContactUs::create([
            'name' => $data['name'] ?? 'Guest',
            'email' => $data['email'] ?? 'guest@example.com',
            'subject' => $data['subject'] ?? 'No Subject',
            'message' => $data['message'] ?? 'Empty Message',
            'user_id' => $data['user_id'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully.',
            'data' => $contact
        ], 201);
    }
}