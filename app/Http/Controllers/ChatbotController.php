<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\JobPosting;
use App\Models\LeaveApplication;
use App\Models\AttendanceRecord;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array',
        ]);

        $user = auth()->user();
        $systemInstruction = "You are a helpful, professional, and friendly AI assistant for the company ERP and HR Management System. You answer questions based on the provided context data. Be concise and format your responses clearly using markdown. If you are asked something outside the context or something you don't know, politely say you don't have that information.";

        if ($user->user_type === 'hr') {
            // HR Context: Load overview of all data
            $employees = Employee::with(['department', 'designation', 'branch'])->get();
            $leaves = LeaveApplication::with('employee')->where('status', 'pending')->get();
            $jobs = JobPosting::with('category')->get();
            $candidates = Candidate::all();
            $applications = JobApplication::with(['candidate', 'job'])->get();
            $users = User::all();

            $contextData = [
                'role' => 'HR Administrator',
                'user_name' => $user->name,
                'system_users' => $users->toArray(),
                'company_employees' => $employees->toArray(),
                'pending_leave_applications' => $leaves->toArray(),
                'job_postings' => $jobs->toArray(),
                'candidates' => $candidates->toArray(),
                'job_applications' => $applications->toArray(),
            ];

            $systemInstruction .= "\n\nYou are speaking to an HR Administrator. Here is the full company context:\n" . json_encode($contextData);
        } else {
            // Employee Context: Load only their personal data
            $employee = $user->employee()->with([
                'department', 'designation', 'branch',
                'attendanceRecords' => fn($q) => $q->latest('date')->take(30),
                'leaveApplications' => fn($q) => $q->latest()->take(10),
                'meetings' => fn($q) => $q->where('date', '>=', now()->toDateString())->take(5),
                'salaryProfile',
                'contracts'
            ])->first();

            $contextData = [
                'role' => 'Employee',
                'user_name' => $user->name,
                'employee_record' => $employee ? $employee->toArray() : null,
            ];

            $systemInstruction .= "\n\nYou are speaking to an Employee. Here is their personal data context. Answer their questions regarding their attendance, leaves, payroll, or meetings based on this data:\n" . json_encode($contextData);
        }

        $reply = $this->geminiService->chat($request->messages, $systemInstruction);

        if ($reply) {
            return response()->json(['reply' => $reply]);
        }

        return response()->json(['error' => 'Failed to generate response from AI.'], 500);
    }
}
