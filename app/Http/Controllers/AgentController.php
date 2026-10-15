<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::withCount('invoices')->orderBy('name')->get();
        $earned = [];
        foreach ($agents as $a) {
            $earned[$a->id] = $a->invoices()->sum('agent_commission');
        }

        return view('agents.index', ['agents' => $agents, 'earned' => $earned]);
    }

    public function create()
    {
        return view('agents.form', ['agent' => new Agent()]);
    }

    public function store(Request $request)
    {
        Agent::create($this->validated($request));

        return redirect()->route('agents.index')->with('success', 'Commission agent saved.');
    }

    public function edit(Agent $agent)
    {
        return view('agents.form', ['agent' => $agent]);
    }

    public function update(Request $request, Agent $agent)
    {
        $agent->update($this->validated($request));

        return redirect()->route('agents.index')->with('success', 'Commission agent updated.');
    }

    private function validated(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}
