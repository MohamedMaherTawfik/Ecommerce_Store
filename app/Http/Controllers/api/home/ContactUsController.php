<?php

namespace App\Http\Controllers\api\home;

use App\Http\Controllers\concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactUs\StoreContactUsRequest;
use App\Models\ContactUs;

class ContactUsController extends Controller
{
    use ApiResponse;

    public function store(StoreContactUsRequest $request)
    {
        $data = $request->validated();

        if ($request->user('sanctum')) {
            $user = $request->user('sanctum');
            $data['user_id'] = $user->id;
            $data['name'] = $user->name;
            $data['email'] = $user->email;
        }

        $contact = ContactUs::create([
            'name' => $data['name'] ?? 'Guest',
            'email' => $data['email'] ?? 'guest@example.com',
            'subject' => $data['subject'] ?? 'No Subject',
            'message' => $data['message'] ?? 'Empty Message',
            'user_id' => $data['user_id'] ?? null,
        ]);

        return $this->success($contact, 'Your message has been sent successfully.');
    }
}
