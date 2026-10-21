<?php
$modelsPath = __DIR__ . '/school_class_progress/app/Models';

$models = [
    'Role' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $timestamps = false;
    protected $fillable = ['role_name'];

    public function users() {
        return $this->hasMany(User::class);
    }
}
EOT,

    'Room' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    public $timestamps = false;
    protected $fillable = ['room_name'];

    public function schedules() {
        return $this->hasMany(Schedule::class);
    }
}
EOT,

    'SchoolClass' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';
    public $timestamps = false;
    protected $fillable = ['class_name', 'wali_kelas_id', 'guru_bk_id'];

    public function waliKelas() {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }
    public function guruBk() {
        return $this->belongsTo(User::class, 'guru_bk_id');
    }
    public function schedules() {
        return $this->hasMany(Schedule::class, 'class_id');
    }
    public function students() {
        return $this->hasMany(User::class, 'class_id');
    }
}
EOT,

    'User' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    const UPDATED_AT = null;
    protected $fillable = ['role_id', 'parent_id', 'class_id', 'username', 'identity_number', 'password', 'fullname'];
    protected $hidden = ['password'];

    public function role() {
        return $this->belongsTo(Role::class);
    }
    public function parent() {
        return $this->belongsTo(User::class, 'parent_id');
    }
    public function children() {
        return $this->hasMany(User::class, 'parent_id');
    }
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
    public function schedulesAsTeacher() {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }
    public function leaveRequestsAsStudent() {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }
}
EOT,

    'Schedule' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public $timestamps = false;
    protected $fillable = ['teacher_id', 'class_id', 'room_id', 'subject', 'day_of_week', 'start_time', 'end_time', 'cycle_type'];

    public function teacher() {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
    public function room() {
        return $this->belongsTo(Room::class);
    }
    public function journals() {
        return $this->hasMany(Journal::class);
    }
}
EOT,

    'Journal' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['schedule_id', 'date', 'topic_description', 'photo_path'];

    public function schedule() {
        return $this->belongsTo(Schedule::class);
    }
    public function attendances() {
        return $this->hasMany(Attendance::class);
    }
}
EOT,

    'Attendance' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    public $timestamps = false;
    protected $fillable = ['journal_id', 'student_id', 'status', 'date'];

    public function journal() {
        return $this->belongsTo(Journal::class);
    }
    public function student() {
        return $this->belongsTo(User::class, 'student_id');
    }
}
EOT,

    'LeaveRequest' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    public $timestamps = false;
    protected $fillable = ['student_id', 'applicant_id', 'verified_by', 'start_date', 'end_date', 'reason', 'document_path', 'status_approval'];

    public function student() {
        return $this->belongsTo(User::class, 'student_id');
    }
    public function applicant() {
        return $this->belongsTo(User::class, 'applicant_id');
    }
    public function verifier() {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
EOT,

    'SystemConfiguration' => <<<'EOT'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    public $timestamps = false;
    protected $fillable = ['active_cycle', 'is_schedule_locked', 'updated_by'];

    public function updater() {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
EOT,
];

foreach ($models as $name => $content) {
    file_put_contents($modelsPath . '/' . $name . '.php', $content);
    echo "Updated $name\\n";
}
