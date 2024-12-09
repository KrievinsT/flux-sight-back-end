<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('sendSMS')->nullable()->after('img');
            $table->boolean('sendEmail')->nullable()->after('sendSMS');
            $table->boolean('sendAlerts')->nullable()->after('sendEmail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sendSMS', 'sendEmail', 'sendAlerts']);
        });
    }
}
?>
