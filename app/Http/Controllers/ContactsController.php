<?php

namespace App\Http\Controllers;

use App\Contacts;
use Illuminate\Http\Request;
use Validator;

class ContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get contacts and parse to Json
        $contacts = Contacts::get()->toJson(JSON_PRETTY_PRINT);

        //send response to client
        return response($contacts, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate contact names
        $validatedData = Validator::make($request->all(),[
        'first_name' => 'required|max:255',
        'last_name' => 'required|max:255',
    	]);
	
        //check if the names were valid
	if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }
        
        //find Contact if it exists
        $findContacts = Contacts::where('first_name', $request->first_name)
            ->where('last_name', $request->last_name)->count();

        //if the contact exist, tell client it exit and avoid duplication
        if($findContacts > 0 ) {
            return response()->json([
                "message" => "Record already exists"], 404);
        }

        //create contact instance and put in names to store to database
	$contacts = new Contacts;
	$contacts->first_name = $request->first_name;
	$contacts->last_name = $request->last_name;
	$contacts->save();

        //send reponse to client of success
	return response()->json([
            "message" => "Contact record created"
            ], 201);
	
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param   $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //validate contact names
        $validatedData = Validator::make($request->all(),[
        'first_name' => 'required|max:255',
        'last_name' => 'required|max:255',
        ]);

        //check if the names were valid
        if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }

        // check if the contact exists
        if (Contacts::where('id', $id)->exists()) {
            //find the contacts and its resources and put in new values to store
            $contacts = Contacts::find($id);

            $contacts->first_name = $request->first_name;
            $contacts->last_name = $request->last_name;
            $contacts->save();

            //send reponse to client of success
            return response()->json([
                "message" => "Contact record updated"
                ], 200);

	}else {
            //send reponse to client that client dont exist
            return response()->json([
                "message" => "Contact not found"
                ], 404);
        }
    }
   
    /**
     * Merge the specified resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function merge(Request $request)
    {
        //find contacts to be merge
        $contactOne = Contacts::find($request->id_one);
        $contactTwo = Contacts::find($request->id_two);

        //new instance for contacts to be created
        $contacts = new Contacts;
        $contacts->first_name = $contactOne->first_name . ", " . $contactTwo->first_name;
        $contacts->last_name = $contactOne->last_name . ", " . $contactTwo->last_name;

        //make stored json data into array for emails
        $emailArrayOne = json_decode($contactOne->email_addresses, true) ?? array();
        $emailArrayTwo = json_decode($contactTwo->email_addresses, true) ?? array();

        //check for duplicates
        foreach($emailArrayOne as $emailOne) {
            foreach($emailArrayTwo as $emailKey => $emailTwo) {
                if($emailOne["email_address"] == $emailTwo["email_address"]) {
                    unset($emailArrayTwo[$emailKey]);
                }
            }
        }

        //merge contact emails
        $mergedEmailArray = array_values(array_merge($emailArrayOne, $emailArrayTwo));

        //reset the IDs for no duplicate IDs
        for($i = 0; $i < count($mergedEmailArray); $i++) {
            $mergedEmailArray[$i]["id"] = $i + 1;
        }

        //set to contact instance to be stored
        $contacts->email_addresses = json_encode($mergedEmailArray);

        //make stored json data into array for phones
        $phoneArrayOne = json_decode($contactOne->phone_numbers, true) ?? array();
        $phoneArrayTwo = json_decode($contactTwo->phone_numbers, true) ?? array();
        
        //check for duplicates
        foreach($phoneArrayOne as $phoneOne) {
            foreach($phoneArrayTwo as $phoneKey => $phoneTwo) {
                if($phoneOne["phone_number"] == $phoneTwo["phone_number"]) {
                    unset($phoneArrayTwo[$phoneKey]);
                }
            }
        }

        //merge contact phones
        $mergedPhoneArray = array_values(array_merge($phoneArrayOne, $phoneArrayTwo));

        //reset the IDs for no duplicate IDs
        for($i = 0; $i < count($mergedPhoneArray); $i++) {
            $mergedPhoneArray[$i]["id"] = $i + 1;
        }

        //set to contact instance to be stored
        $contacts->phone_numbers = json_encode($mergedPhoneArray);

        //save new merge contacts
        $contacts->save();

        //remove old records
        $contactOne->delete();
        $contactTwo->delete();
        
        //send reponse back to client of success
        return response()->json([
                "message" => "Contacts have been merged"
                ], 201);
    }
   
}
