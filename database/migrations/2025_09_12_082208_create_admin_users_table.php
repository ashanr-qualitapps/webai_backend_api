use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminUsersTable extends Migration
{
    public function up()
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('full_name')->nullable();
            $table->jsonb('permissions')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestampTz('last_login')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_updated')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_users');
    }
}
