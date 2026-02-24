<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';

    private const STATUS_LABELS = [
        self::STATUS_REQUESTED => 'Solicitado',
        self::STATUS_ARRIVED => 'Asistio',
        self::STATUS_PAID => 'Pagado',
        self::STATUS_COMPLETED => 'Completado',
    ];

    private const STATUS_BADGES = [
        self::STATUS_REQUESTED => 'secondary',
        self::STATUS_ARRIVED => 'warning',
        self::STATUS_PAID => 'success',
        self::STATUS_COMPLETED => 'info',
    ];

    protected $fillable = [
        'patient_first_name',
        'patient_last_name',
        'phone',
        'patient_email',
        'dni',
        'specialty_id',
        'doctor_id',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(AppointmentNotificationLog::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function canMarkArrived(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function canMarkPaid(): bool
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_ARRIVED], true);
    }
}
