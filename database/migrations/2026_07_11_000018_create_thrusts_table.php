<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thrusts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->text('keywords')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('thrusts')->insert([
            [
                'name' => 'Food Security, Self-sufficiency and Safety',
                'description' => 'Research on agriculture, fisheries, food production, sufficiency, affordability, and safe food systems.',
                'keywords' => 'food security, agriculture, fisheries, livestock, crop, farm, nutrition, food safety, food production, self-sufficiency',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Climate Change',
                'description' => 'Research on climate impacts, resilience, mitigation, adaptation, weather extremes, and emissions.',
                'keywords' => 'climate change, climate, global warming, resilience, mitigation, adaptation, emissions, carbon, weather, extreme heat, drought, flood',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Environmental Resource Management',
                'description' => 'Research on land, air, water, ecosystems, conservation, pollution, and resource protection.',
                'keywords' => 'environment, ecosystem, conservation, pollution, waste, water quality, soil, air quality, biodiversity, resource management, forestry',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Human Health and Nutrition',
                'description' => 'Research on health, disease prevention, public health, sanitation, hygiene, medicine, and nutrition.',
                'keywords' => 'health, nutrition, disease, public health, sanitation, hygiene, medicine, vaccine, diagnostic, wellness, malnutrition',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Disaster Risk Reduction and Management',
                'description' => 'Research on hazard preparedness, disaster resilience, emergency response, mitigation, and recovery.',
                'keywords' => 'disaster, risk reduction, risk management, preparedness, hazard, emergency, evacuation, resilience, typhoon, earthquake, flood',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sustainable Renewable Energy Sources',
                'description' => 'Research on renewable energy, efficiency, energy security, and clean power systems.',
                'keywords' => 'renewable energy, solar, wind, biomass, energy, electricity, power, energy efficiency, clean energy, microgrid, sustainability',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Emerging Technologies',
                'description' => 'Research on artificial intelligence, robotics, biotechnology, nanotechnology, information technology, and other new technologies.',
                'keywords' => 'technology, artificial intelligence, ai, machine learning, robotics, biotechnology, nanotechnology, information technology, automation, data science, system',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Social Sciences',
                'description' => 'Research on society, economics, entrepreneurship, governance, higher education, and law.',
                'keywords' => 'social science, society, economics, entrepreneurship, education, governance, law, policy, community, behavior, livelihood',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('thrusts');
    }
};