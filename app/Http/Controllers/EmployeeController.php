<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RealRashid\SweetAlert\Facades\Alert;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use PDF;


class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $pageTitle = 'Employee List';

        confirmDelete();

        return view('employee.index', compact('pageTitle'));

        // $pageTitle = 'Employee List';

        //ELOQUENT
        // $employees = Employee::all();

        // //QUERY BUILDER
        // $employee = DB::table('employees')
        //     ->select('*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->leftJoin ('positions', 'employees.position_id', 'positions.id')
        //     ->get();

        //  // RAW SQL QUERY
        // $employees = DB::select('
        // select *, employees.id as employee_id, positions.name as position_name
        // from employees
        // left join positions on employees.position_id = positions.id
        // ');

        // return view('employee.index', [
        // 'pageTitle' => $pageTitle,
        // 'employees' => $employees
        // ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pageTitle = 'Create Employee';

        //ELOQUENT
        $positions = Position::all();

        // //QUERY BUILDER
        // $positions = DB::table('positions')->get();
        // // RAW SQL Query
        // $positions = DB::select('select * from positions');

        return view('employee.create', compact('pageTitle', 'positions'));
    }
    // //QUERY BUILDER
        // $positions = DB::table('positions')->get();
        // // RAW SQL Query
        // $positions = DB::select('select * from positions');

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        // Get File
        $file = $request->file('cv');

        if ($file != null) {
            $originalFilename = $file->getClientOriginalName();
            $encryptedFilename = $file->hashName();

        // Store File
        $file->store('public/files');
    }

        // ELOQUENT
        $employee = New Employee;
        $employee->firstname = $request->firstName;
        $employee->lastname = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;
        if ($file != null) {
            $employee->original_filename = $originalFilename;
            $employee->encrypted_filename = $encryptedFilename;
        }
        $employee->save();

        // // INSERT QUERY
        // DB::table('employees')->insert([
        //     'firstname' => $request->firstName,
        //     'lastname' => $request->lastName,
        //     'email' => $request->email,
        //     'age' => $request->age,
        //     'position_id' => $request->position,
        // ]);
        Alert::success('Added Successfully', 'Employee Data Added Successfully.');

        return redirect()->route('employees.index');
        // return $request->all();
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pageTitle = 'Employee Detail';

        //ELOQUENT
        $employee = Employee::find($id);

        // // Query Builder
        // $employee = DB::table('employees')
        //     ->select('*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->leftJoin ('positions', 'employees.position_id', 'positions.id')
        //     ->where ('employees.id', $id)
        //     ->first();

        // RAW SQL QUERY
        // $employee = collect(DB::select('
        //     select *, employees.id as employee_id, positions.name as position_name
        //     from employees
        //     left join positions on employees.position_id = positions.id
        //     where employees.id = ?
        // ', [$id]))->first();

        return view('employee.show', compact('pageTitle', 'employee'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pageTitle = 'Edit Employee';

        //ELOQUENT
        $positions = Position::all();
        $employee = Employee::find($id);
        // //QUERY BUILDER
        // $positions = DB::table('positions')->get();

        // // Query Builder
        // $employee = DB::table('employees')
        //     ->select('*', 'employees.id as employee_id', 'positions.name as position_name')
        //     ->leftJoin ('positions', 'employees.position_id', 'positions.id')
        //     ->where ('employees.id', $id)
        //     ->first();
        // // RAW SQL Query
        // $positions = DB::select('select * from positions');
        return view('employee.edit', compact('pageTitle', 'positions', 'employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $messages = [
            'required' => ':Attribute harus diisi.',
            'email' => 'Isi :attribute dengan format yang benar',
            'numeric' => 'Isi :attribute dengan angka'
        ];

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric',
        ], $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $file = $request->file('cv');

        if ($file != null) {
            $originalFilename = $file->getClientOriginalName();
            $encryptedFilename = $file->hashName();
        }

        // ELOQUENT
        $employee = Employee::find($id);
        $employee->firstName = $request->firstName;
        $employee->lastName = $request->lastName;
        $employee->email = $request->email;
        $employee->age = $request->age;
        $employee->position_id = $request->position;
        if ($request->hasFile('cv')) {
            $file = $request->file('cv');

            // Simpan file baru
            $file->store('public/files');

            // Hapus file lama
            Storage::delete('public/files/'.$employee->encrypted_filename);

            // Update nama file baru dalam model
            if ($file != null) {
                $employee->original_filename = $originalFilename;
                $employee->encrypted_filename = $encryptedFilename;
            }
        }
        $employee->save();
        // $pageTitle = 'Edit Detail';

        // // Query Builder
        // DB::table('employees')
        //     ->where('id', $id)
        //     ->update([
        //         'firstName' => $request->firstName,
        //         'lastName' => $request->lastName,
        //         'email' => $request->email,
        //         'age' => $request->age,
        //         'position_id' => $request->position,
        //     ]);

        Alert::success('Changed Successfully', 'Employee Data Changed Successfully.');

        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
{
        //
           // QUERY BUILDER
        // DB::table('employees')
        // ->where('id', $id)
        // ->delete();

    // ELOQUENT
    // Employee::find($id)->delete();
    $employee = Employee::find($id);
    $file ='public/files/'.$employee->encrypted_filename;


if (!empty($file)) {
    // Hapus file dari direktori public
    Storage::delete('/' . $file);
}

    // Hapus entitas dari database
    $employee->delete();

    Alert::success('Deleted Successfully', 'Employee Data Deleted Successfully.');

    return redirect()->route('employees.index');
}


public function downloadFile($employeeId)
{
    $employee = Employee::find($employeeId);
    $encryptedFilename = 'public/files/'.$employee->encrypted_filename;
    $downloadFilename = Str::lower($employee->firstName.'_'.$employee->lastName.'_cv.pdf');

    if(Storage::exists($encryptedFilename)) {
        return Storage::download($encryptedFilename, $downloadFilename);
    }
}

public function getData(Request $request)
{
    $employees = Employee::with('position');

    if ($request->ajax()) {
        return datatables()->of($employees)
            ->addIndexColumn()
            ->addColumn('actions', function($employee) {
                return view('employee.actions', compact('employee'));
            })
            ->toJson();
    }
}

public function exportExcel()
{
    return Excel::download(new EmployeesExport, 'employees.xlsx');
}

public function exportPdf()
{
    $employees = Employee::all();

    $pdf = PDF::loadView('employee.export_pdf', compact('employees'));

    return $pdf->download('employees.pdf');
}

}
