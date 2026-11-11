<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock ledger row. Never edited or deleted. Corrections are new adjustment rows.
 */
class StockTransaction extends Model
{
    protected $table = 'pm_stock_transactions';
    protected $guarded = ['id'];
    protected $casts = ['transaction_date' => 'date'];

    public const TYPES = [
        'opening'        => 'Opening Stock',
        'receipt'        => 'Receipt',
        'issue'          => 'Issue',
        'return'         => 'Return',
        'adjustment_in'  => 'Adjustment In',
        'adjustment_out' => 'Adjustment Out',
    ];

    /** Types that increase stock */
    public const IN_TYPES = ['opening', 'receipt', 'return', 'adjustment_in'];

    public function spare(): BelongsTo
    {
        return $this->belongsTo(Spare::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function issueTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'issue_transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isInward(): bool
    {
        return in_array($this->type, self::IN_TYPES);
    }
}
