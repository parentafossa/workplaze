<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//namespace TCG\Voyager\Http\Controllers;
use mikehaertl\pdftk\Pdf;
use App\Models\DriverActivity;
use App\Models\CompanyCar;

use App\Models\DriverLog;
use App\Models\DriverTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\CashAdvanceRequest;
use App\Models\CashAdvanceUsage;
use App\Models\DriverTripAssignment;
;

class GetEAController extends Controller
{
    public function getEmpattend(Request $request, $tdName, $salPeriod, $empId=null)
    {
        // Replace the following lines with proper validation and sanitation for the modelName and parameter
        // For example, you can create a whitelist of allowed table names and check if the modelName is in the list
        // Never trust user input directly for table names, model names, or parameters
        $user=$request->user();

        $data = DB::table('vemp_attendperiods')
            ->where('cost_center', 'like', $tdName? $tdName:'' . '%')
            ->when(!empty($salPeriod), function ($query) use ($salPeriod) {
                return $query->where('period', 'like', $salPeriod . '%');
            })
            ->where('emp_id','like', $empId? $empId:'' . '%')
            ->get();
        
            
        return response()->json($data);
        
    }

    public function uploadslip(Request $request)
    {
        $user = auth()->user(); // Get the authenticated user

        // You can now perform any checks on the user object
        // For example, check if the user has the right permissions to upload a file
        /* if (!$user->canUploadFiles()) {
            return response()->json(['error' => 'You do not have permission to upload files'], 403);
        } */

        if($request->hasFile('file')) {
            $file = $request->file('file');
            $name = time().'.'.$file->getClientOriginalExtension();
            $destinationPath = public_path('/uploads');
            $file->move($destinationPath, $name);

            // Set password for the PDF file
            $pdf = new Pdf($destinationPath.'/'.$name);
            $password = $request->input('password');
            $pdf->setPassword($password)->saveAs($destinationPath.'/'.$name);

            return response()->json(['success'=>'File Uploaded and Password Set Successfully']);
        } else {
            return response()->json(['error'=>'No File Uploaded']);
        }
    }


	public function getDriverActivity(Request $request)
    {     
        $user=$request->user();
        $flag = $request->query('flag');
		$sequence = $request->query('sequence');
		
		$query = DriverActivity::where('active', 1);

        if ($flag) {
			$query->where('flag', $flag);
		}
		if ($sequence !== null) {
			$query->where('sequence', (int) $sequence);
		}
		    $activities = $query->orderBy('ordering', 'asc')->get();

		$total = $activities->count();
        return response()->json([
        'status' => true,
        'message' => [
            'total' => $total,
            'data' => $activities->map(function ($activity) {
                return [
                    'Id' 		=> $activity->id,
                    'flag' 		=> $activity->flag,
                    'menuValue' => $activity->name,
                    'sequence' 	=> $activity->sequence,
                    'colorCode' => $activity->color_code,
                    'action' 	=> $activity->action,
                ];
            })
        ]
    ]);
        
    }
    
    public function getTruckList(Request $request)
    {
        // Replace the following lines with proper validation and sanitation for the modelName and parameter
        // For example, you can create a whitelist of allowed table names and check if the modelName is in the list
        // Never trust user input directly for table names, model names, or parameters
        $user=$request->user();

        $trucks = CompanyCar::where('type', 'truck')
        ->where('active',1)
        ->get();
            
        return response()->json($trucks);
        
    }

    public function getDriverList(Request $request)
    {
        $user=$request->user();
        $trucks = DB::table('emp_current_information')
        ->where('status','<>','Inactive')
        ->where('job_code','like','DV%');        
		
		if ($request->has('emplid') && !empty($request->emplid)) {
			$trucks->where('emp_id', $request->emplid);
		}

        $drivers = $trucks->get();
            
        return response()->json($drivers);	
        
    }

    public function LogDriver(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required|string',
            'assignment_id' => 'required|integer',
            'driveraction_id' => 'required|integer',
            'driveraction_type' => 'required|string',
            'driver_timestamp' => 'required|date_format:Y-m-d H:i:s', // Expecting a date in this format'			
            'truck_no' => 'required|string|max:20',
            'device_info' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'address' => 'nullable|string',
            'remark' => 'nullable|string'
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Convert the passed driver_timestamp into a valid Carbon timestamp
        $driverTimestamp = Carbon::parse($request->driver_timestamp);
        $driverManualTimestamp = Carbon::parse($request->driver_manual_time);

        // Create a new DriverLog record
        $driverLog = DriverLog::create([
            'emp_id' => $request->emp_id,
            'assignment_id' => $request->assignment_id,
            'driveraction_id' => $request->driveraction_id,
            'driveraction_type' => $request->driveraction_type,
            'driver_timestamp' => $driverTimestamp, // Convert to timestamp
            'driver_manual_time' => $driverManualTimestamp, // Convert to timestamp
            'truck_no' => $request->truck_no,
            'device_info' => $request->device_info,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'altitude' => $request->altitude,
            'speed' => $request->speed,
            'address' => $request->address,
            'remark' => $request->remark
        ]);

        //$tripId = DriverTripAssignment::where('id',$request->assignment_id)->pluck('trip_id');

        // Return a success response with the created record
        return response()->json([
            'message' => 'Driver log created successfully',
            'data' => $driverLog
        ], 201);
    }

    public function getLastStatus(Request $request)
    {
        $user = $request->user();

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required|string',
            'truck_no' => 'required|string|max:20',
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new DriverLog record
        $driverLog = DriverLog::where('emp_id', $request->emp_id)
            ->where('truck_no', $request->truck_no)
            ->orderBy('driver_timestamp', 'desc') // Get the latest log entry by timestamp
            ->first();


        // Check if a log was found
        if (!$driverLog) {
            return response()->json(['message' => 'No driver log found for the given emp_id and truck_no'], 404);
        }

        // Return the found log entry
        return response()->json($driverLog, 200);

    }

    public function getDriverTrip(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'truck_no' => 'required|string',
            'emp_id' => 'required|integer',
        ]);

        $truckNo = $request->input('truck_no');
        $empId = $request->input('emp_id');

        // Query for DriverTrip where truck_no matches, and status is not 'completed' or 'canceled'
        $driverTrip = DriverTrip::where('truck_no', $truckNo)
            ->whereNotIn('status', ['completed', 'canceled'])
            ->whereHas('assignments', function ($query) use ($empId) {
                $query->where('driver_id', $empId);
            })
            ->with(['assignments' => function ($query) use ($empId) {
                $query->where('driver_id', $empId)
                    ->with('cashAdvanceRequests:id,driver_trip_assignment_id');
            }])
            ->first();

        // Check if a driver trip was found
        if (!$driverTrip) {
            return response()->json([
                'message' => 'No active or incomplete driver trip found for this truck and driver',
            ], 404);
        }

        // Prepare response data
        $data = [
            'driver_trip_id' => $driverTrip->id,
            'truck_no' => $driverTrip->truck_no,
            'assignments' => [],
        ];

        foreach ($driverTrip->assignments as $assignment) {
            $data['assignments'][] = [
                'assignment_id' => $assignment->id,
                'driver_id' => $assignment->driver_id,
                'cash_advance_request_id' => optional($assignment->cashAdvanceRequests->first())->id,
            ];
        }

        return response()->json($data);
    }

    public function cashUsage(Request $request)
    {
        // Validate the input data
        $validatedData = $request->validate([
            'caah_adv_req_id' => 'required',
            'usage_datetime' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'purpose_id' => 'required|exists:ca_purposes,id',
            'remarks' => 'nullable|string',
            'files.*' => 'file|mimes:jpeg,png,pdf|max:5120', // Optional files
        ]);

        // Create the Cash Advance Usage record
        $cashAdvanceUsage = CashAdvanceUsage::create([
            'cash_adv_req_id'=> $validatedData['cash_advance_request_id'],
            'usage_datetime' => $validatedData['usage_datetime'],
            'amount' => $validatedData['amount'],
            'purpose_id' => $validatedData['purpose_id'],
            'remarks' => $validatedData['remarks'] ?? null,
        ]);

        // Handle file uploads, if provided
        if ($request->hasFile('files')) {
            $filePaths = [];

            foreach ($request->file('files') as $file) {
                // Define the file name with cash advance usage ID and timestamp
                $timestamp = now()->format('Ymd_His');
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$cashAdvanceUsage->id}_{$timestamp}." . $extension;

                // Define the directory based on CashAdvanceRequest ID
                $directory = "cash_adv_usages/{$request->cash_adv_req_id}";

                // Store the file in the directory on the 'public' disk
                $filePath = $file->storeAs($directory, $fileName, 'private');
                $filePaths[] = $filePath;
            }

            // Save the file paths in the database if needed
            $cashAdvanceUsage->files = $filePaths; // Assuming you have a 'files' field in the model as JSON
            $cashAdvanceUsage->save();
        }

        return response()->json([
            'message' => 'Cash Advance Usage created successfully.',
            'data' => $cashAdvanceUsage,
        ], 201);
    }
}

