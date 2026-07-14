<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string("ticket_number");
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('ticket_category_id');
            $table->unsignedBigInteger('ticket_priority_id');
            $table->string("title");
            $table->text("description")->nullable();
            $table->integer("sla");
            $table->timestamp("due_time");
            $table->enum("status", ["OPEN", "ASSIGNED", "IN_PROGRESS", "PENDING", "RESOLVED", "CLOSED", "REJECTED"]);
            $table->timestamp("resolved_at")->nullable();
            $table->timestamp("closed_at")->nullable();
            $table->timestamps();

            $table->foreign("requester_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("assigned_to")->references("id")->on("users")->onDelete("cascade");
            $table->foreign("ticket_category_id")->references("id")->on("ticket_categories")->onDelete("cascade");
            $table->foreign("ticket_priority_id")->references("id")->on("ticket_priorities")->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
