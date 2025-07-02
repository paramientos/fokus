<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('nationality')->nullable()->after('gender');
            $table->string('national_id')->nullable()->after('nationality');
            $table->string('passport_number')->nullable()->after('national_id');
            $table->string('tax_id')->nullable()->after('passport_number');
            $table->text('address')->nullable()->after('tax_id');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('state');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('emergency_contact_relationship')->nullable()->after('country');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'gender',
                'nationality',
                'national_id',
                'passport_number',
                'tax_id',
                'address',
                'city',
                'state',
                'postal_code',
                'country',
                'emergency_contact_relationship',
            ]);
        });
    }
};
