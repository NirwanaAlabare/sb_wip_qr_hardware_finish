<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SignalBit\Undo;
use App\Models\SignalBit\MasterPlan;

class UndoContent extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $masterPlan;
    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $masterPlan = session()->get('orderInfo');
        $this->masterPlan = $masterPlan ? $masterPlan->id : null;
        $this->dateFrom = $this->dateFrom ? $this->dateFrom : date('Y-m-d');
        $this->dateTo = $this->dateTo ? $this->dateTo : date('Y-m-d');
    }

    public function render()
    {
        $masterPlan = session()->get('orderInfo');
        $this->masterPlan = $masterPlan ? $masterPlan->id : null;

        // $latestOutput = DB::select(DB::raw("
        //     SELECT output_rfts.created_at, output_rfts.updated_at FROM output_rfts
        //     LEFT JOIN master_plan ON master_plan.id = output_rfts.master_plan_id
        //     WHERE master_plan.sewing_line = '".Auth::user()->username."'
        //     UNION
        //     SELECT output_defects.created_at, output_defects.updated_at FROM output_defects
        //     LEFT JOIN master_plan ON master_plan.id = output_defects.master_plan_id
        //     WHERE master_plan.sewing_line = '".Auth::user()->username."'
        //     UNION
        //     SELECT output_rejects.created_at, output_rejects.updated_at FROM output_rejects
        //     LEFT JOIN master_plan ON master_plan.id = output_rejects.master_plan_id
        //     WHERE master_plan.sewing_line = '".Auth::user()->username."'
        //     UNION
        //     SELECT output_reworks.created_at, output_reworks.updated_at FROM output_reworks
        //     LEFT JOIN output_defects ON output_defects.id = output_reworks.defect_id
        //     LEFT JOIN master_plan ON master_plan.id = output_defects.master_plan_id
        //     WHERE master_plan.sewing_line = '".Auth::user()->username."'
        //     ORDER BY updated_at DESC, created_at DESC
        //     LIMIT 10
        // "));

        $latestUndoSql = Undo::selectRaw('output_undo_finish.updated_at, output_undo_finish.keterangan, so_det.size as size, count(*) as total')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_undo_finish.master_plan_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_undo_finish.so_det_id');
            if (Auth::user()->Groupp != 'ALLSEWING') {
                $latestUndoSql->where('master_plan.sewing_line', Auth::user()->username);
            }
            if ($this->masterPlan) {
                $latestUndoSql->where('master_plan.id', $this->masterPlan);
            }
        $latestUndo = $latestUndoSql->whereRaw("DATE(output_undo_finish.created_at) BETWEEN '".$this->dateFrom."' AND '".$this->dateTo."'")->
            whereRaw("master_plan.tgl_plan BETWEEN '".$this->dateFrom."' AND '".$this->dateTo."'")->
            groupBy("output_undo_finish.updated_at", "output_undo_finish.keterangan", "so_det.size")->
            orderBy("output_undo_finish.updated_at", "desc")->
            orderBy("output_undo_finish.created_at", "desc")->
            paginate(10, ['*'], 'latestUndoPage');

        return view('livewire.undo-content', [
            // 'latestOutput' => $latestOutput,
            'latestUndo' => $latestUndo,
        ]);
    }
}
