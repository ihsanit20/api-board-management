<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SiteSetting;

class SiteSettingsController extends Controller
{
    // Show Scrolling Notice
    public function showScrollingNotice()
    {
        $setting = SiteSetting::first();
        return response()->json(['scrolling_notice' => $setting->scrolling_notice]);
    }

    // Update Scrolling Notice
    public function updateScrollingNotice(Request $request)
    {
        $setting = SiteSetting::first();
        $setting->scrolling_notice = $request->scrolling_notice;
        $setting->save();

        return response()->json(['message' => 'Scrolling notice updated successfully']);
    }

    // Show Director Message
    public function showDirectorMessage()
    {
        $setting = SiteSetting::first();
        return response()->json(['director_message' => $setting->director_message]);
    }

    // Update Director Message
    public function updateDirectorMessage(Request $request)
    {
        $setting = SiteSetting::first();
        $setting->director_message = $request->director_message;
        $setting->save();

        return response()->json(['message' => 'Director message updated successfully']);
    }

    // Show About Us
    public function showAboutUs()
    {
        $setting = SiteSetting::first();
        return response()->json(['about_us' => $setting->about_us]);
    }

    // Update About Us
    public function updateAboutUs(Request $request)
    {
        $setting = SiteSetting::first();
        $setting->about_us = $request->about_us;
        $setting->save();

        return response()->json(['message' => 'About us updated successfully']);
    }
}
