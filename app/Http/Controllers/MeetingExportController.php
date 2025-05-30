<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MeetingExportController extends Controller
{
    /**
     * Export meetings as iCalendar file
     */
    public function exportICalendar(Request $request, $id = null)
    {
        $query = Meeting::query()->with(['project', 'users']);
        
        // Check if ID is for a meeting or project
        if ($id) {
            $meeting = Meeting::find($id);
            $project = Project::find($id);
            
            if ($meeting) {
                $query->where('id', $id);
            } elseif ($project) {
                $query->where('project_id', $id);
            }
        }
        
        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('scheduled_at', [$startDate, $endDate]);
        } else {
            // Default to next 30 days if no specific meeting is requested
            if (!$meeting) {
                $query->whereBetween('scheduled_at', [now(), now()->addDays(30)]);
            }
        }
        
        // Filter by meeting type if provided
        if ($request->has('meeting_type') && $request->meeting_type !== 'all') {
            $query->where('meeting_type', $request->meeting_type);
        }
        
        $meetings = $query->get();
        
        // Generate iCalendar content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Projecta//Meeting Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        
        foreach ($meetings as $meeting) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . $meeting->id . "@projecta.com\r\n";
            $ical .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
            $ical .= "DTSTART:" . $meeting->scheduled_at->format('Ymd\THis\Z') . "\r\n";
            $ical .= "DTEND:" . $meeting->scheduled_at->addMinutes($meeting->duration)->format('Ymd\THis\Z') . "\r\n";
            $ical .= "SUMMARY:" . $this->escapeString($meeting->title) . "\r\n";
            
            if ($meeting->description) {
                $ical .= "DESCRIPTION:" . $this->escapeString($meeting->description) . "\r\n";
            }
            
            $ical .= "LOCATION:" . $this->escapeString($meeting->project->name . ' - Projecta') . "\r\n";
            
            // Add organizer
            $ical .= "ORGANIZER;CN=" . $this->escapeString($meeting->creator->name) . ":mailto:" . $meeting->creator->email . "\r\n";
            
            // Add attendees
            foreach ($meeting->users as $attendee) {
                $ical .= "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=" . $this->escapeString($attendee->name) . ":mailto:" . $attendee->email . "\r\n";
            }
            
            $ical .= "END:VEVENT\r\n";
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        // Set filename
        $filename = 'meetings';
        if ($id) {
            if ($meeting) {
                $filename = strtolower(str_replace(' ', '_', $meeting->title));
            } elseif ($project) {
                $filename = strtolower(str_replace(' ', '_', $project->name)) . '_meetings';
            }
        }
        
        // Return as downloadable file
        return Response::make($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.ics"',
        ]);
    }
    
    /**
     * Export meetings as CSV file
     */
    public function exportCsv(Request $request, $id = null)
    {
        $query = Meeting::query()->with(['project', 'creator', 'users']);
        
        // Check if ID is for a meeting or project
        if ($id) {
            $meeting = Meeting::find($id);
            $project = Project::find($id);
            
            if ($meeting) {
                $query->where('id', $id);
            } elseif ($project) {
                $query->where('project_id', $id);
            }
        }
        
        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('scheduled_at', [$startDate, $endDate]);
        } else {
            // Default to next 30 days if no specific meeting is requested
            if (!$meeting) {
                $query->whereBetween('scheduled_at', [now(), now()->addDays(30)]);
            }
        }
        
        // Filter by meeting type if provided
        if ($request->has('meeting_type') && $request->meeting_type !== 'all') {
            $query->where('meeting_type', $request->meeting_type);
        }
        
        $meetings = $query->get();
        
        // Create CSV content
        $headers = [
            'Title',
            'Project',
            'Type',
            'Date',
            'Time',
            'Duration',
            'Status',
            'Organizer',
            'Attendees',
            'Description'
        ];
        
        $callback = function() use ($meetings, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($meetings as $meeting) {
                $row = [
                    $meeting->title,
                    $meeting->project->name,
                    ucfirst($meeting->meeting_type),
                    $meeting->scheduled_at->format('Y-m-d'),
                    $meeting->scheduled_at->format('H:i'),
                    $meeting->duration . ' minutes',
                    ucfirst(str_replace('_', ' ', $meeting->status)),
                    $meeting->creator->name,
                    $meeting->users->pluck('name')->implode(', '),
                    $meeting->description
                ];
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        // Set filename
        $filename = 'meetings';
        if ($id) {
            $meeting = Meeting::find($id);
            $project = Project::find($id);
            
            if ($meeting) {
                $filename = strtolower(str_replace(' ', '_', $meeting->title));
            } elseif ($project) {
                $filename = strtolower(str_replace(' ', '_', $project->name)) . '_meetings';
            }
        }
        
        // Return as downloadable file
        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }
    
    /**
     * Escape special characters in string for iCalendar format
     */
    private function escapeString($string)
    {
        $string = str_replace(["\r\n", "\n"], "\\n", $string);
        $string = str_replace([",", ";", "\\"], ["\\,", "\\;", "\\\\"], $string);
        return $string;
    }
}
