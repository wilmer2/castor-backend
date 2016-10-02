<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Models\Audit;

class AuditController extends Controller {

  public function index() {
    $audits = Audit::orderBy('created_at', 'desc')->get();
    return response()->json($audits);
  }
}