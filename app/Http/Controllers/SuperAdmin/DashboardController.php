<?php

namespace App\Http\Controllers\SuperAdmin;

use DB, Auth;
use Carbon\Carbon;
use App\Models\Unit;
use App\Models\User;
use App\Models\Staff;
use App\Models\School;
use App\Models\Stock;
use App\Models\Student;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\HeadTeacherLog;
use App\Models\StaffAttendance;
use App\Models\StudentAttendance;
use App\Http\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Services\SchoolService;
use App\Http\Services\DashboardService;
use Anuzpandey\LaravelNepaliDate\LaravelNepaliDate;

class DashboardController extends Controller
{
    protected $dashboardService;
    protected $schoolService;

    public function __construct(DashboardService $dashboardService, SchoolService $schoolService)
    {
        $this->dashboardService = $dashboardService;
        $this->schoolService = $schoolService;
    }

    public function index()
    {
        $page_title = Auth::user()->getRoleNames()[0] . ' ' . "Dashboard";

        // General counts (all schools for Super Admin)
        $totalStudents = Student::count();
        $totalGirls = Student::whereHas('user', function ($query) {
            $query->where('gender', 'female');
        })->count();
        $totalBoys = Student::whereHas('user', function ($query) {
            $query->where('gender', 'male');
        })->count();

        $today = Carbon::today()->format('Y-m-d');
        $nepaliDateToday = LaravelNepaliDate::from($today)->toNepaliDate();

        $presentStudents = StudentAttendance::where('attendance_type_id', 1)
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStudents = StudentAttendance::where('attendance_type_id', 2)
            ->where('date', $nepaliDateToday)
            ->count();

        $totalStaffs = Staff::count();
        $presentStaffs = StaffAttendance::where('attendance_type_id', 1)
            ->where('date', $nepaliDateToday)
            ->count();
        $absentStaffs = StaffAttendance::where('attendance_type_id', 2)
            ->where('date', $nepaliDateToday)
            ->count();

        $major_incidents = HeadTeacherLog::where('logged_date', $nepaliDateToday)->count();
        $todays_major_incidents = HeadTeacherLog::where('logged_date', $nepaliDateToday)
            ->get(['major_incidents', 'school_id']);

        // School data for all schools (Super Admin sees all)
        $schools = School::all();
        $schoolData = [];
        foreach ($schools as $school) {
            $schoolId = $school->id;
            $totalStudentsInSchool = Student::where('school_id', $schoolId)->count();
            $presentStudentsInSchool = StudentAttendance::where('attendance_type_id', 1)
                ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $absentStudentsInSchool = StudentAttendance::where('attendance_type_id', 2)
                ->whereHas('student', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $totalStaffsInSchool = Staff::where('school_id', $schoolId)->count();
            $presentStaffsInSchool = StaffAttendance::where('attendance_type_id', 1)
                ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $absentStaffsInSchool = StaffAttendance::where('attendance_type_id', 2)
                ->whereHas('staff', fn($q) => $q->where('school_id', $schoolId))
                ->where('date', $nepaliDateToday)
                ->count();
            $schoolData[] = [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'school_address' => $school->address ?? '',
                'total_students' => $totalStudentsInSchool,
                'present_students' => $presentStudentsInSchool,
                'absent_students' => $absentStudentsInSchool,
                'total_staffs' => $totalStaffsInSchool,
                'present_staffs' => $presentStaffsInSchool,
                'absent_staffs' => $absentStaffsInSchool,
            ];
        }

        $totalSchools = School::count();
        $school_students = $this->schoolService->getSchoolStudent();
        $school_students_count = $this->schoolWiseCountOfStudent($school_students);
        $school_staffs = $this->schoolService->getSchoolStaff();
        $school_staffs_count = $this->schoolWiseCountOfStaff($school_staffs);
        $school_wise_student_attendences = $this->schoolService->getSchoolWiseStudentAttendence();
        $schoolWiseStudentData = $this->getSchoolWiseStudentData();

        $schools_wise_reports = collect();

        return view('backend.municipality_admin.dashboard.dashboard', [
            'page_title' => $page_title,
            'presentStudents' => $presentStudents,
            'totalStudents' => $totalStudents,
            'absentStudents' => $absentStudents,
            'totalStaffs' => $totalStaffs,
            'presentStaffs' => $presentStaffs,
            'absentStaffs' => $absentStaffs,
            'schoolData' => $schoolData,
            'major_incidents' => $major_incidents,
            'totalSchools' => $totalSchools,
            'totalGirls' => $totalGirls,
            'totalBoys' => $totalBoys,
            'todays_major_incidents' => $todays_major_incidents,
            'school_staffs ' => $school_staffs,
            'school_staffs_count' => $school_staffs_count,
            'school_students_count' => $school_students_count,
            'school_wise_student_attendences' => $school_wise_student_attendences,
            'schoolWiseStudentData' => $schoolWiseStudentData,
            'schools_wise_reports' => $schools_wise_reports,
        ]);
    }

    private function getSchoolWiseStudentData()
    {
        $schoolWiseStudents = Student::join('schools', 'students.school_id', '=', 'schools.id')
            ->select('schools.name as school_name', DB::raw('count(students.id) as total_students'))
            ->groupBy('schools.name')
            ->get()
            ->toArray();

        $labels = array_column($schoolWiseStudents, 'school_name');
        $dataValues = array_column($schoolWiseStudents, 'total_students');

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'School wise Student Count',
                    'data' => $dataValues,
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    public function schoolWiseCountOfStudent($originalData)
    {
        // Initialize labels and data arrays
        $labels = [];
        $data = [];

        // Iterate over the original data array
        foreach ($originalData as $item) {
            $labels[] = $item['name'];
            $data[] = $item['total_student'];
        }

        // Construct the required data structure
        $finalData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'School wise Student Count',
                    'data' => $data,
                    'borderWidth' => 1
                ]
            ]
        ];
        return $finalData;
    }
    public function schoolWiseCountOfStaff($originalData)
    {
        // Initialize labels and data arrays
        $labels = [];
        $data = [];

        // Iterate over the original data array
        foreach ($originalData as $item) {
            $labels[] = $item['name'];
            $data[] = $item['total_staffs'];
        }

        // Construct the required data structure
        $finalData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'School wise Staffs Count',
                    'data' => $data,
                    'borderWidth' => 1
                ]
            ]
        ];
        return $finalData;
    }


}