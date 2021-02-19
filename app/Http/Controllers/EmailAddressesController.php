<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contacts;
use Validator;

class EmailAddressesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        //find the contact that have the email addresses
        $contacts = Contacts::find($id);
        $emailJson = $contacts->email_addresses;
        return response($emailJson, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // check if the email is valid
        $validatedData = Validator::make($request->all(),[
            'email_address' => 'required|email:rfc',
        ]);

        //if email is not valid, send error to client
        if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }

        //find the conatact that have the email addresses
        $contacts = Contacts::find($request->id);

        //get the email addresses
        $emailJson = $contacts->email_addresses;

        //variable used to state if an email address is the primary address
        $primary = 0;

        //variable used to create IDs for emails
        $id = 0;

        //check if a list of email exist, if not, set new email to primary
        if(!isset($emailJson)){
            $primary = 1;
            $emailArray = array();
            $id = 1;
        } else {
        
            //change list of emails from Json to Arrays
            $emailArray = json_decode($emailJson, true);
 
            //find the key of duplicate email if it exist
            $emailArrayKey = array_search($request->email_address, array_column($emailArray, 'email_address'));

            //if email exist already, send client error message
            if($emailArrayKey !== false) {
                
                return response()->json([
                    "message" => "Email Already Exists"], 404);

            } 

            //get the key of the last email in the array
            $lastEmailKey = array_key_last($emailArray);

            //increment the id for the new email
            $id = $emailArray[$lastEmailKey]["id"] + 1;
            
        }

        //add the new email to the list of emails
        array_push($emailArray,array("id"=>$id,"primary"=>$primary, "email_address" => $request->email_address));

        //change email list from Arrays to Json
        $updatedEmailJson = json_encode($emailArray);

        //store email to database
        $contacts->email_addresses = $updatedEmailJson;
        $contacts->save();

        //send success status and message to client
        return response()->json([
            "message" => "Email created"
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
        //validate email and the primary status
        $validatedData = Validator::make($request->all(),[
            'email_address' => 'required|email:rfc',
            'primary' => 'numeric'
        ]);

        //check if the inputs was valid and if not, send client error status
        if ($validatedData->fails()) {
            return response()->json([
                "message" => "Validation failed"], 404);
        }

        //find the contact that has the email address
        $contacts = Contacts::find($id);

        //get the list of email addresses
        $emailJson = $contacts->email_addresses;

        //change the list of emails from Json to Arrays
        $emailArray = json_decode($emailJson, true);

        //check if the updated email exist already
        $emailCheckExist = array_search($request->email_address, array_column($emailArray, 'email_address'));

        //if the email exist, send client error
        if($emailCheckExist !== false) {
            return response()->json([
                "message" => "Cannot change email to already existing email"], 404);
        }

        //get the key for the email
        $emailArrayKey = array_search($request->id, array_column($emailArray, 'id'));

        //check if the primary status is set
        if($request->primary == 1) {
            //change all primary status of emails to 0
            foreach($emailArray as &$email) {
                $email["primary"] = 0;
            }
            //set the updated email to be primary
            $emailArray[$emailArrayKey]["primary"] = $request->primary;
        }

        //updated the email address
        $emailArray[$emailArrayKey]["email_address"] = $request->email_address;

        //change the email list from Array to Json
        $updatedEmailJson = json_encode($emailArray);

        //set the email and store to database
        $contacts->email_addresses = $updatedEmailJson;    

        $contacts->save();

        //send client success status
        return response()->json([
            "message" => "Email updated"
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
        //get the contact with the email we want to delete
        $contacts = Contacts::find($id);

        //get the list of emails
        $emailJson = $contacts->email_addresses;

        //change the list of email from Json to Arrays
        $emailArray = json_decode($emailJson, true);

        //get the key of the email we want to delete
        $emailArrayKey = array_search($request->email_address_id, array_column($emailArray, 'id'));

        //if the key dont exit then the email doesn't exist. send error statu
        //with messege that the email is not found
        if($emailArrayKey === false) {
            return response()->json([
                "message" => "cannot find email to delete"
            ], 404);
        }

        //if the email is the primary email, send message that email is primary and cant be deleted
        if($emailArray[$emailArrayKey]["primary"] == 1) {
            return response()->json([
                "message" => "cannot delete primary email"
            ], 404);
        }

        //delete the email
        unset($emailArray[$emailArrayKey]);

        //change the email list from Array to Json
        $updatedEmailJson = json_encode(array_values($emailArray));

        //store into the database
        $contacts->email_addresses = $updatedEmailJson;
        $contacts->save();

        //send client status that the record was deleted
        return response()->json([
            "message" => "record deleted"
        ], 202);
    }
}
