<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contacts;
use Validator;

class PhoneNumbersController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        //find the contact resource to get phone numbers
        $contacts = Contacts::find($id);
        $phoneJson = $contacts->phone_numbers;
        //send phone numbers as Json to client
        return response($phoneJson, 200);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate phone number
        $validatedData = Validator::make($request->all(),[
            'phone_number' => 'required|digits:10',
        ]);

        //check if the phone number is good and return
        if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }

        //find the Contact record and get the phone records
        $contacts = Contacts::find($request->id);
        $phoneJson = $contacts->phone_numbers;

        //variable use to state if the number is a primary number
        $primary = 0;

        //variable use for the phone number id
        $id = 0;

        //check if there is any data for phone numbers. if not,
        //then its the first inserted number and make it the primary
        if(!isset($phoneJson)){
            $primary = 1;
            $phoneArray = array();
            $id = 1;
        } else {

            //make the json list of phone numbers to an array
            $phoneArray = json_decode($phoneJson, true);

            //search if the phone number exist
            $phoneArrayKey = array_search($request->phone_number, array_column($phoneArray, 'phone_number'));

            // if the phone number exist, send a error message
            if($phoneArrayKey !== false) {

                return response()->json([
                    "message" => "Phone Number Already Exists"], 404);

            }

            //get the last key of the last array 
            $lastPhoneKey = array_key_last($phoneArray);

            //increment the ID
            $id = $phoneArray[$lastPhoneKey]["id"] + 1;

        }

        //add the new phone number to the list of phone number
        array_push($phoneArray,array("id"=>$id,"primary"=>$primary, "phone_number" => $request->phone_number));

        //make it into Json to store in the database
        $updatedPhoneJson = json_encode($phoneArray);

        $contacts->phone_numbers = $updatedPhoneJson;
        $contacts->save();

        //let the client know that the phone number is stored in the database
        return response()->json([
            "message" => "Phone Number created"
            ], 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
       //validate the phone number and primary status
       $validatedData = Validator::make($request->all(),[
            'phone_number' => 'required|digits:10',
            'primary' => 'numeric'
        ]);

        //if the validation failes, send error back to user stating it failed
        if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }

        //get the stored instance for the contact
        $contacts = Contacts::find($id);

        //get the list of Phone numbers that is in Json
        $phoneJson = $contacts->phone_numbers;

        //make the phone list into an Array
        $phoneArray = json_decode($phoneJson, true);

        //check if the phone number already exit
        $phoneCheckExist = array_search($request->phone_number, array_column($phoneArray, 'phone_number'));

        //if phone number already exit, send error back to client
        if($phoneCheckExist !== false) {
            return response()->json([
                "message" => "Cannot change Phone number to already existing phone number"], 404);
        }

        //get the key of the phone number that is being updated
        $phoneArrayKey = array_search($request->id, array_column($phoneArray, 'id'));

        //check if the phone number is going to be the new primary number
        if($request->primary == 1) {
            //change all others to non primary
            foreach($phoneArray as &$phone) {
                $phone["primary"] = 0;
            }
            //set the updated phone number to the new primary
            $phoneArray[$phoneArrayKey]["primary"] = $request->primary;
        }

        //update the phone number and change to json and store to database
        $phoneArray[$phoneArrayKey]["phone_number"] = $request->phone_number;
        $updatedPhoneJson = json_encode($phoneArray);

        $contacts->phone_numbers = $updatedPhoneJson;

        $contacts->save();

        //send client success message
        return response()->json([
            "message" => "Phone Number updated"
            ], 200);
 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //find the contacts that have the phone number
        $contacts = Contacts::find($id);

        //get the phone numbers
        $phoneJson = $contacts->phone_numbers;

        //change the list of phone numbers from Json to Arrays
        $phoneArray = json_decode($phoneJson, true);

        //find the key of the phone number that is being deleted
        $phoneArrayKey = array_search($request->phone_number_id, array_column($phoneArray, 'id'));

        //check if the phone number exits, if not send client an error message
        if($phoneArrayKey === false) {
            return response()->json([
                "message" => "cannot find Phone Number to delete"
            ], 404);
        }

        //check if its a primary number and if so, tell client primary cannot be removed
        if($phoneArray[$phoneArrayKey]["primary"] == 1) {
            return response()->json([
                "message" => "cannot delete primary Phone Number"
            ], 404);
        }

        //removed the phone number
        unset($phoneArray[$phoneArrayKey]);

        //change the list of Array of phone numbers to Json
        $updatedPhoneJson = json_encode(array_values($phoneArray));

        //store and save to database
        $contacts->phone_numbers = $updatedPhoneJson;
        $contacts->save();

        //send message that delection was successful
        return response()->json([
            "message" => "record deleted"
        ], 202);
    }
}
