<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\College;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdditionalResearchSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $approver = User::whereIn('role', ['super_admin', 'admin'])->orderBy('id')->first();
        $colleges = College::all();
        $categories = Category::all();

        if ($students->isEmpty() || $colleges->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $papers = [
            [
                'title' => 'AI-Assisted Crop Disease Detection for Smallholder Farms in CALABARZON',
                'college' => 'CIT',
                'abstract' => 'This study evaluates a low-cost image classification pipeline for early crop disease detection among smallholder farmers in CALABARZON.',
                'introduction' => 'Crop disease outbreaks reduce farm income and food security. Farmers need affordable tools that can identify symptoms before severe damage occurs.',
                'methodology' => 'The study trained a convolutional neural network on 18,400 annotated leaf images and validated the model with field tests across 42 farms.',
                'results' => 'The final model reached 91.3% validation accuracy and reduced average diagnosis time from 3 days to less than 10 minutes during pilot deployment.',
                'discussion' => 'Results indicate that mobile-first AI tools can support extension workers and farmers, but dataset drift and connectivity constraints remain important challenges.',
                'references' => 'Mohanty, S. P., Hughes, D. P., & Salathe, M. (2016). Using deep learning for image-based plant disease detection. Frontiers in Plant Science, 7, 1419.',
                'conclusion' => 'AI-assisted detection is feasible for smallholder contexts and can substantially reduce diagnosis delays.',
                'recommendations' => 'Scale pilots through DA extension offices and expand training datasets with locally captured disease variants.',
                'keywords' => 'crop disease detection, computer vision, agriculture, CALABARZON',
                'status' => Research::STATUS_PENDING_RDE,
            ],
            [
                'title' => 'Learning Analytics Dashboard Adoption Among Public School Teachers',
                'college' => 'CTED',
                'abstract' => 'This research analyzes teacher adoption of a learning analytics dashboard and its effects on formative intervention decisions.',
                'introduction' => 'Dashboards promise better visibility into learner progress, yet adoption barriers persist in public schools.',
                'methodology' => 'A six-month implementation in 12 schools combined usage logs, surveys, and focus group interviews with 96 teachers.',
                'results' => 'Teachers who used the dashboard weekly improved on-time intervention rates by 27% compared with baseline classroom practices.',
                'discussion' => 'Adoption improved when school heads allocated protected data-review time and peer mentoring support.',
                'references' => 'Ifenthaler, D., & Yau, J. Y. K. (2020). Utilising learning analytics for study success.',
                'conclusion' => 'Dashboard use can strengthen formative assessment workflows when organizational support is present.',
                'recommendations' => 'Institutionalize weekly data meetings and provide ongoing dashboard coaching for new users.',
                'keywords' => 'learning analytics, teacher adoption, dashboard, formative assessment',
                'status' => Research::STATUS_APPROVED,
            ],
            [
                'title' => 'Mobile Wallet Utilization and Budgeting Behavior of First-Year Students',
                'college' => 'CBEA',
                'abstract' => 'This study explores how mobile wallet usage frequency relates to budgeting discipline among first-year college students.',
                'introduction' => 'Cashless payments are increasingly common among students, but their effects on budgeting behavior are mixed.',
                'methodology' => 'A survey of 540 students was analyzed using correlation and regression models controlling for allowance size and financial literacy.',
                'results' => 'Frequent wallet users were associated with higher budget tracking scores when paired with spending alerts; without alerts, overspending incidence increased.',
                'discussion' => 'The behavioral effect of mobile wallets depends on built-in nudges, not merely payment convenience.',
                'references' => 'Soman, D. (2001). Effects of payment mechanism on spending behavior.',
                'conclusion' => 'Digital payment tools can improve budgeting if alert and categorization features are actively used.',
                'recommendations' => 'Universities should include personal finance onboarding modules tied to digital payment settings.',
                'keywords' => 'mobile wallet, budgeting, financial behavior, students',
                'status' => Research::STATUS_REVISION_COLLEGE,
                'revision_notes' => 'Provide reliability coefficients for the budgeting scale and clarify treatment of missing responses.',
            ],
            [
                'title' => 'Rainwater Harvesting Performance in Coastal Campus Facilities',
                'college' => 'CFAS',
                'abstract' => 'This paper measures the operational performance of campus rainwater harvesting systems in coastal municipalities.',
                'introduction' => 'Water stress during dry months motivates institutions to adopt supplementary non-potable water sources.',
                'methodology' => 'Three campus sites were monitored for inflow, storage losses, and end-use demand over 11 months.',
                'results' => 'Systems supplied an average of 31% of non-potable demand and reduced municipal water consumption by 18%.',
                'discussion' => 'Design sizing and first-flush maintenance schedules were key determinants of system effectiveness.',
                'references' => 'Campisano, A., et al. (2017). Urban rainwater harvesting systems: Research, implementation and future perspectives.',
                'conclusion' => 'Rainwater harvesting can provide meaningful demand offset when maintenance protocols are enforced.',
                'recommendations' => 'Adopt quarterly maintenance audits and include storage resilience modeling in new campus projects.',
                'keywords' => 'rainwater harvesting, water management, coastal campus, sustainability',
                'status' => Research::STATUS_APPROVED,
            ],
            [
                'title' => 'Simulation-Based Driver Training and Road Safety Competence',
                'college' => 'CCJE',
                'abstract' => 'This study compares simulation-based training with lecture-only instruction for improving novice driver safety competence.',
                'introduction' => 'Traffic incidents involving novice drivers remain high. Simulation offers risk-free exposure to hazard scenarios.',
                'methodology' => 'A controlled trial with 124 trainees compared pre/post hazard perception, rules recall, and reaction-time metrics.',
                'results' => 'The simulation group improved hazard perception scores by 34% and reaction time by 19% relative to controls.',
                'discussion' => 'Scenario diversity and debrief quality strongly influenced learning transfer to practical assessments.',
                'references' => 'Fisher, D. L., Pollatsek, A., & Pradhan, A. K. (2006). Can novice drivers be trained to scan for information?',
                'conclusion' => 'Simulation-based modules provide measurable gains in road safety competence for novice drivers.',
                'recommendations' => 'Embed simulator sessions in standard driver education and standardize debrief rubrics.',
                'keywords' => 'driver training, simulation, hazard perception, road safety',
                'status' => Research::STATUS_PENDING_COLLEGE,
            ],
            [
                'title' => 'Telehealth Follow-Up Compliance in Community Hypertension Programs',
                'college' => 'CHM',
                'abstract' => 'This research examines whether telehealth follow-up improves compliance in community hypertension management programs.',
                'introduction' => 'Hypertension control requires sustained follow-up, yet clinic attendance remains inconsistent in many communities.',
                'methodology' => 'A comparative cohort of 380 patients tracked adherence rates between standard care and telehealth-augmented follow-up.',
                'results' => 'Telehealth participants achieved 22% higher follow-up compliance and modestly better blood pressure control over six months.',
                'discussion' => 'Remote check-ins reduced travel burden and improved continuity, though digital access disparities affected older patients.',
                'references' => 'Keesara, S., Jonas, A., & Schulman, K. (2020). Covid-19 and health care digital revolution.',
                'conclusion' => 'Telehealth can improve follow-up adherence in chronic disease programs when access barriers are managed.',
                'recommendations' => 'Provide assisted telehealth kiosks in barangay health stations and train staff for digital triage.',
                'keywords' => 'telehealth, hypertension, adherence, community health',
                'status' => Research::STATUS_APPROVED,
            ],
            [
                'title' => 'Research Data Management Practices in Philippine Graduate Programs',
                'college' => 'GS',
                'abstract' => 'This study maps research data management practices and repository readiness across graduate programs in state universities.',
                'introduction' => 'Data stewardship is essential for reproducibility, yet many institutions lack formal policies and infrastructure.',
                'methodology' => 'Policy review, repository audit, and faculty survey were conducted in 9 universities with 210 graduate advisers.',
                'results' => 'Only 28% of programs had written data retention policies, and only 19% required dataset archiving at thesis completion.',
                'discussion' => 'Policy absence, storage constraints, and unclear ownership rules were the most frequent barriers.',
                'references' => 'Wilkinson, M. D., et al. (2016). The FAIR guiding principles for scientific data management and stewardship.',
                'conclusion' => 'Graduate programs need coordinated policy, infrastructure, and training to reach baseline data stewardship maturity.',
                'recommendations' => 'Adopt minimum data management policy standards and provide institutional repository support units.',
                'keywords' => 'research data management, graduate school, repository, FAIR principles',
                'status' => Research::STATUS_REVISION_RDE,
                'revision_notes' => 'Expand repository audit criteria and include inter-rater reliability for policy coding.',
            ],
            [
                'title' => 'Solar-Powered Cold Storage Feasibility for Municipal Fish Landing Sites',
                'college' => 'CFAS',
                'abstract' => 'This feasibility study evaluates solar-powered cold storage units to reduce post-harvest fish losses in municipal landing sites.',
                'introduction' => 'Post-harvest spoilage undermines fisher incomes, especially in sites with unstable grid power.',
                'methodology' => 'Energy-load simulation and a 4-month pilot trial were conducted at two landing sites with daily catch logging.',
                'results' => 'Pilot sites showed a 26% reduction in spoilage losses and improved average selling prices through better product quality retention.',
                'discussion' => 'Operational sustainability depends on governance models for user fees and preventive maintenance.',
                'references' => 'Aung, M. M., & Chang, Y. S. (2014). Traceability in a food supply chain: Safety and quality perspectives.',
                'conclusion' => 'Solar cold storage is technically viable and can significantly reduce spoilage in off-grid or weak-grid settings.',
                'recommendations' => 'Scale through cooperatives with maintenance funds and technician training support.',
                'keywords' => 'cold storage, solar energy, fisheries, post-harvest loss',
                'status' => Research::STATUS_APPROVED,
            ],
        ];

        foreach ($papers as $i => $paper) {
            $college = $colleges->where('code', $paper['college'])->first();
            $student = $students->get($i % $students->count());
            $category = $categories->get($i % $categories->count());

            $status = $paper['status'] ?? Research::STATUS_APPROVED;
            $isApproved = $status === Research::STATUS_APPROVED;

            Research::firstOrCreate(
                ['title' => $paper['title']],
                [
                    'abstract' => $paper['abstract'],
                    'introduction' => $paper['introduction'],
                    'methodology' => $paper['methodology'],
                    'results' => $paper['results'],
                    'discussion' => $paper['discussion'],
                    'references' => $paper['references'] ?? null,
                    'conclusion' => $paper['conclusion'] ?? null,
                    'recommendations' => $paper['recommendations'] ?? null,
                    'keywords' => $paper['keywords'] ?? 'research, Philippines',
                    'authors' => $student ? $student->name . ', et al.' : 'Unknown Author',
                    'college_id' => $college ? $college->id : $colleges->first()->id,
                    'category_id' => $category ? $category->id : $categories->first()->id,
                    'user_id' => $student ? $student->id : $students->first()->id,
                    'status' => $status,
                    'approved_by' => $isApproved ? $approver?->id : null,
                    'approved_at' => $isApproved ? now() : null,
                    'revision_notes' => $paper['revision_notes'] ?? null,
                    'publication_year' => random_int(2021, 2026),
                ]
            );
        }
    }
}
