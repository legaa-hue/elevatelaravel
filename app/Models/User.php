<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'role',
        'google_id',
        'profile_picture',
        'status',
        'is_active',
        'activation_token',
        'activation_token_expires_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'activation_token_expires_at' => 'datetime',
        ];
    }
    
    /**
     * Generate and set activation token
     */
    public function generateActivationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->activation_token = hash('sha256', $token);
        $this->activation_token_expires_at = now()->addHours(24);
        $this->save();
        
        return $token; // Return plain token for email
    }
    
    /**
     * Activate user account
     */
    public function activate(): bool
    {
        $this->is_active = true;
        $this->activation_token = null;
        $this->activation_token_expires_at = null;
        $this->email_verified_at = now();
        
        return $this->save();
    }
    
    /**
     * Check if activation token is valid
     */
    public function isActivationTokenValid(string $token): bool
    {
        if (!$this->activation_token || !$this->activation_token_expires_at) {
            return false;
        }
        
        if ($this->activation_token_expires_at->isPast()) {
            return false;
        }
        
        return hash('sha256', $token) === $this->activation_token;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is teacher
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Get push subscriptions for this user (WebPush feature)
     * Returns empty collection if WebPush package is not installed
     */
    public function pushSubscriptions()
    {
        if (class_exists('NotificationChannels\WebPush\PushSubscription')) {
            return $this->morphMany('NotificationChannels\WebPush\PushSubscription', 'subscribable');
        }
        return collect([]);
    }

    /**
     * Route notification for WebPush channel
     */
    public function routeNotificationForWebPush()
    {
        if (class_exists('NotificationChannels\WebPush\PushSubscription')) {
            return $this->pushSubscriptions;
        }
        return collect([]);
    }

    /**
     * Get courses where user is the teacher/instructor
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    /**
     * Get courses that user has joined
     */
    public function joinedCourses()
    {
        return $this->belongsToMany(Course::class, 'joined_courses', 'user_id', 'course_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }
    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
