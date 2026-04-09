<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Research;
use App\Models\User;
use App\Models\College;
use App\Models\Category;

class ResearchSeeder extends Seeder
{
    public function run()
    {
        $students = User::where('role', 'student')->get();
        $colleges = College::all();
        $categories = Category::all();

        $papers = [
            [
                'title' => 'Artificial Intelligence in Educational Technology: Enhancing Student Learning Outcomes',
                'college' => 'CICS',
                'abstract' => 'This study examines the integration of artificial intelligence tools in educational settings and their measurable impact on student learning outcomes across Philippine universities.',
                'introduction' => 'The rapid advancement of AI technology has created new opportunities for enhancing education. This study investigates how AI-powered tools can be effectively integrated into the Philippine educational system to improve student performance and engagement.',
                'methodology' => "A mixed-methods approach was employed, combining quantitative surveys of 500 students across 5 universities with qualitative interviews of 30 educators. Data was collected over two semesters using pre- and post-assessment scores.\n\nThe system development process used the Agile Model, as illustrated below:\n\n[figure: agile_model.png | Figure 1. Agile Development Model]\n\nThe Agile approach allowed for iterative development and continuous feedback from end-users throughout the research period.",
                'results' => "Students using AI-enhanced learning platforms showed significant improvements across all measured metrics. The following table summarizes the key findings:\n\n| Metric | Control Group | AI-Enhanced Group | Improvement |\n| Average Test Score | 72.4% | 89.1% | +23% |\n| Student Engagement | 58% | 78.3% | +35% |\n| Satisfaction Rating | 3.1/5.0 | 4.2/5.0 | +35% |\n| Assignment Completion | 71% | 88% | +24% |\n| Course Retention | 82% | 94% | +15% |\n\nThe overall assessment of the AI-enhanced platform using a 5-point Likert scale revealed high levels of acceptability across all dimensions:\n\n| Category | Mean | Descriptive Value |\n| Content Quality | 4.35 | Very High Extent |\n| System Usability | 4.12 | High Extent |\n| Learning Effectiveness | 4.28 | Very High Extent |\n| Technical Performance | 3.95 | High Extent |\n| Overall Mean | 4.18 | High Extent |",
                'discussion' => 'The findings suggest that AI integration significantly enhances learning outcomes when properly implemented. However, challenges remain in infrastructure, teacher training, and equitable access across urban and rural institutions.',
                'references' => "Baker, R. S., & Inventado, P. S. (2014). Educational data mining and learning analytics. In J. A. Larusson & B. White (Eds.), Learning analytics (pp. 61–75). Springer. https://doi.org/10.1007/978-1-4614-3305-7_4\nHolmes, W., Bialik, M., & Fadel, C. (2019). Artificial intelligence in education: Promises and implications for teaching and learning. Center for Curriculum Redesign.\nLuckin, R., Holmes, W., Griffiths, M., & Forcier, L. B. (2016). Intelligence unleashed: An argument for AI as a tool for teachers. Pearson Education.\nPopenici, S. A. D., & Kerr, S. (2017). Exploring the impact of artificial intelligence on teaching and learning in higher education. Research and Practice in Technology Enhanced Learning, 12(1), Article 22. https://doi.org/10.1186/s41039-017-0062-8\nZawacki-Richter, O., Marín, V. I., Bond, M., & Gouverneur, F. (2019). Systematic review of research on artificial intelligence applications in higher education. International Journal of Educational Technology in Higher Education, 16(1), Article 39.",
                'conclusion' => 'AI integration in educational technology significantly improves student learning outcomes when properly implemented with adequate infrastructure and teacher training. The 23% improvement in test scores and 35% increase in engagement demonstrate the transformative potential of AI-enhanced learning platforms in Philippine higher education.',
                'recommendations' => 'Universities should invest in AI-powered learning management systems and provide comprehensive training for educators. Government agencies should allocate funding for digital infrastructure in underserved institutions. Future research should explore long-term impacts of AI integration on student retention and career readiness.',
            ],
            [
                'title' => 'Machine Learning Approaches for Predicting Academic Performance in Philippine Universities',
                'college' => 'CICS',
                'abstract' => 'This research develops and evaluates machine learning models for early prediction of student academic performance to enable timely interventions.',
                'introduction' => 'Academic performance prediction is critical for student retention. This study explores how machine learning algorithms can identify at-risk students early in the semester using enrollment and early assessment data.',
                'methodology' => 'Historical academic records of 10,000 students were analyzed using Random Forest, SVM, and Neural Network classifiers. Features included attendance, quiz scores, demographic data, and LMS activity logs.',
                'results' => "The Random Forest model achieved the highest accuracy among the three classifiers tested. The comparison of model performance is summarized below:\n\n| Model | Accuracy | Precision | Recall | F1-Score |\n| Random Forest | 87.2% | 85.6% | 88.1% | 86.8% |\n| SVM | 81.4% | 79.3% | 82.7% | 80.9% |\n| Neural Network | 84.7% | 83.2% | 85.9% | 84.5% |\n\nThe most influential features for predicting academic performance were identified through feature importance analysis:\n\n| Rank | Feature | Importance Score |\n| 1 | Early Quiz Scores (Weeks 1-4) | 0.312 |\n| 2 | LMS Login Frequency | 0.245 |\n| 3 | Attendance Rate | 0.198 |\n| 4 | Assignment Submission Timeliness | 0.127 |\n| 5 | Discussion Forum Participation | 0.068 |",
                'discussion' => 'Early prediction models can serve as effective early warning systems. The study recommends integrating these models into existing LMS platforms to provide automated alerts to advisers and students.',
                'references' => "Alamri, R., Alharbi, B., & Alshehri, M. (2021). Predicting student academic performance using machine learning: A systematic review. Education and Information Technologies, 26(4), 4067–4090. https://doi.org/10.1007/s10639-021-10498-x\nBreiman, L. (2001). Random forests. Machine Learning, 45(1), 5–32. https://doi.org/10.1023/A:1010933404324\nHellas, A., Ihantola, P., Petersen, A., Ajanovski, V. V., Gutica, M., Hynninen, T., & Liao, S. N. (2018). Predicting academic performance: A systematic literature review. Proceedings Companion of the 23rd Annual ACM Conference on Innovation and Technology in Computer Science Education, 175–199.\nRomero, C., & Ventura, S. (2020). Educational data mining and learning analytics: An updated survey. WIREs Data Mining and Knowledge Discovery, 10(3), Article e1355.",
                'conclusion' => 'Machine learning models, particularly Random Forest, can effectively predict student academic performance with 87% accuracy using early-semester data. These models serve as reliable early warning systems for identifying at-risk students.',
                'recommendations' => 'Educational institutions should integrate predictive analytics into their LMS platforms. Academic advisers should receive training on interpreting model outputs. Future studies should explore ensemble methods and incorporate socioeconomic factors for improved prediction accuracy.',
            ],
            [
                'title' => 'Effectiveness of Online Learning Platforms During the COVID-19 Pandemic in Rural Areas',
                'college' => 'CTED',
                'abstract' => 'This study evaluates the effectiveness of online learning platforms adopted during the COVID-19 pandemic in rural Philippine communities.',
                'introduction' => 'The sudden shift to online learning during COVID-19 exposed digital divides, particularly in rural areas. This research examines how effectively online platforms served students in underserved communities.',
                'methodology' => 'Survey data was collected from 800 students and 120 teachers across 15 rural schools. Internet connectivity tests, device availability audits, and academic performance comparisons were conducted.',
                'results' => 'Only 42% of rural students had reliable internet access. Academic performance dropped by an average of 15% compared to pre-pandemic levels. Asynchronous learning modules were more effective than synchronous sessions.',
                'discussion' => 'The digital divide significantly impacted rural education during the pandemic. Recommendations include investing in community internet infrastructure, distributing devices, and developing offline-capable learning materials.',
                'references' => "Adnan, M., & Anwar, K. (2020). Online learning amid the COVID-19 pandemic: Students' perspectives. Journal of Pedagogical Sociology and Psychology, 2(1), 45–51. https://doi.org/10.33902/JPSP.2020261309\nBozkurt, A., & Sharma, R. C. (2020). Emergency remote teaching in a time of global crisis due to coronavirus pandemic. Asian Journal of Distance Education, 15(1), i–vi.\nToquero, C. M. (2020). Challenges and opportunities for higher education amid the COVID-19 pandemic: The Philippine context. Pedagogical Research, 5(4), Article em0063. https://doi.org/10.29333/pr/7947\nWorld Bank. (2020). Remote learning and COVID-19: The use of educational technologies at scale across an education system. World Bank Group.",
                'conclusion' => 'Online learning platforms were significantly less effective in rural areas due to the digital divide, with only 42% of students having reliable internet access. Asynchronous learning proved more practical than synchronous sessions in low-connectivity environments.',
                'recommendations' => 'Government should invest in community internet infrastructure for rural areas. Schools should develop offline-capable learning materials and provide devices to underserved students. Teacher training programs should include digital pedagogy for blended learning approaches.',
            ],
            [
                'title' => 'Financial Literacy and Its Impact on Personal Investment Decisions Among College Students',
                'college' => 'CBEA',
                'abstract' => 'This research investigates the relationship between financial literacy levels and personal investment behavior among Filipino college students.',
                'introduction' => 'Financial literacy is essential for making informed investment decisions. With increasing access to digital investment platforms, understanding how financial education influences student investment behavior is crucial.',
                'methodology' => 'A cross-sectional survey of 600 college students was conducted using a validated financial literacy questionnaire. Investment behavior was measured through self-reported portfolio data and investment frequency.',
                'results' => 'Students with high financial literacy scores were 3 times more likely to invest regularly. 65% of financially literate students diversified their investments, compared to only 20% of those with low literacy.',
                'discussion' => 'Integrating comprehensive financial literacy programs into college curricula could significantly improve student investment readiness. The study recommends mandatory personal finance courses across all degree programs.',
                'references' => "Lusardi, A., & Mitchell, O. S. (2014). The economic importance of financial literacy: Theory and evidence. Journal of Economic Literature, 52(1), 5–44. https://doi.org/10.1257/jel.52.1.5\nMangundayao, I. T., & Garcia, R. P. (2021). Financial literacy among Filipino college students: A basis for financial education program. International Journal of Business and Management, 16(5), 34–48.\nOECD. (2020). PISA 2018 results (Volume IV): Are students smart about money? OECD Publishing. https://doi.org/10.1787/48ebd1ba-en\nRooij, M. C., Lusardi, A., & Alessie, R. J. (2011). Financial literacy and stock market participation. Journal of Financial Economics, 101(2), 449–472. https://doi.org/10.1016/j.jfineco.2011.03.006",
                'conclusion' => 'Financial literacy has a strong positive correlation with investment behavior among Filipino college students. Financially literate students are three times more likely to invest regularly and demonstrate better portfolio diversification.',
                'recommendations' => 'CHED should mandate personal finance courses across all degree programs. Universities should partner with financial institutions to provide practical investment workshops. Future research should track the long-term financial outcomes of students who received financial literacy education.',
            ],
            [
                'title' => 'Aquaculture Innovation: Sustainable Fish Farming Practices in Laguna de Bay',
                'college' => 'CFAS',
                'abstract' => 'This study evaluates innovative and sustainable aquaculture practices being implemented in Laguna de Bay to balance economic productivity with environmental conservation.',
                'introduction' => 'Laguna de Bay is the largest lake in the Philippines and a critical aquaculture zone. Environmental degradation threatens both the ecosystem and the livelihoods of thousands of fish farmers.',
                'methodology' => 'Field studies were conducted across 25 fish farms over 12 months. Water quality parameters, fish yield data, and economic metrics were compared between traditional and sustainable farming methods.',
                'results' => 'Sustainable practices reduced water pollution by 40% while maintaining 92% of traditional yield levels. Feed conversion ratios improved by 18% using locally-sourced organic feeds.',
                'discussion' => 'Sustainable aquaculture is economically viable in Laguna de Bay. Policy support and farmer education programs are needed to accelerate adoption of environmentally responsible practices.',
                'references' => "Béné, C., Arthur, R., & Norbert, H. (2016). Contribution of fisheries and aquaculture to food security. World Development, 79, 177–196. https://doi.org/10.1016/j.worlddev.2015.11.007\nEdwards, P. (2015). Aquaculture environment interactions: Past, present and likely future trends. Aquaculture, 447, 2–14. https://doi.org/10.1016/j.aquaculture.2015.02.001\nNaylor, R. L., Hardy, R. W., Buschmann, A. H., & Bush, S. R. (2021). A 20-year retrospective review of global aquaculture. Nature, 591(7851), 551–563. https://doi.org/10.1038/s41586-021-03308-6",
                'conclusion' => 'Sustainable aquaculture practices in Laguna de Bay are economically viable, reducing water pollution by 40% while maintaining 92% of traditional yield levels. Organic feed alternatives improved feed conversion ratios by 18%.',
                'recommendations' => 'BFAR should create incentive programs for fish farmers adopting sustainable practices. Local government units should enforce water quality standards. Research institutions should develop region-specific organic feed formulations to further reduce costs.',
            ],
            [
                'title' => 'Renewable Energy Integration in Industrial Manufacturing Processes',
                'college' => 'CIT',
                'abstract' => 'This research examines the feasibility and impact of integrating renewable energy sources into Philippine industrial manufacturing operations.',
                'introduction' => 'The Philippines relies heavily on fossil fuels for industrial energy. This study explores how solar, wind, and biomass energy can be integrated into manufacturing to reduce costs and carbon emissions.',
                'methodology' => 'Energy audits were conducted at 15 manufacturing facilities. Simulation models were developed to project the impact of renewable energy integration on operational costs and emissions over 10 years.',
                'results' => 'Solar panel integration could reduce energy costs by 30% within 5 years. Combined renewable sources could decrease carbon emissions by 45%. Initial investment payback period averaged 3.5 years.',
                'discussion' => 'Renewable energy integration is both environmentally and economically beneficial for Philippine manufacturers. Government incentives and financing programs could accelerate adoption across the industrial sector.',
                'references' => "IRENA. (2020). Renewable energy and jobs: Annual review 2020. International Renewable Energy Agency.\nLund, H. (2014). Renewable energy systems: A smart energy systems approach to the choice and modeling of 100% renewable solutions (2nd ed.). Academic Press.\nOwusu, P. A., & Asumadu-Sarkodie, S. (2016). A review of renewable energy sources, sustainability issues and climate change mitigation. Cogent Engineering, 3(1), Article 1167990. https://doi.org/10.1080/23311916.2016.1167990",
                'conclusion' => 'Renewable energy integration in Philippine manufacturing is both environmentally and economically beneficial, with solar panels reducing energy costs by 30% and combined renewable sources decreasing carbon emissions by 45%.',
                'recommendations' => 'The government should expand renewable energy incentives for manufacturing. Industry associations should facilitate knowledge sharing among early adopters. Future research should explore energy storage solutions to address intermittency challenges in tropical climates.',
            ],
            [
                'title' => 'Community-Based Crime Prevention Strategies in Urban Barangays',
                'college' => 'CCJE',
                'abstract' => 'This study analyzes the effectiveness of community-based crime prevention programs implemented in urban barangays in Metro Manila.',
                'introduction' => 'Community-based approaches to crime prevention emphasize local participation and social cohesion. This research evaluates various community programs and their impact on crime rates in urban barangays.',
                'methodology' => 'A comparative study of 20 barangays was conducted, analyzing crime statistics, community program participation rates, and resident perception surveys over a 3-year period.',
                'results' => 'Barangays with active community watch programs saw a 28% reduction in property crimes. Youth engagement programs reduced juvenile delinquency by 35%. Community satisfaction with safety increased by 40%.',
                'discussion' => 'Community-based programs are effective supplements to traditional policing. Success depends on sustained community engagement, local government support, and adequate resource allocation.',
                'references' => "Lab, S. P. (2020). Crime prevention: Approaches, practices, and evaluations (10th ed.). Routledge.\nTilley, N. (2009). Crime prevention. Willan Publishing.\nWeisburd, D., & Eck, J. E. (2004). What can police do to reduce crime, disorder, and fear? The Annals of the American Academy of Political and Social Science, 593(1), 42–65. https://doi.org/10.1177/0002716203262548\nWelsh, B. C., & Farrington, D. P. (2012). The Oxford handbook of crime prevention. Oxford University Press.",
                'conclusion' => 'Community-based crime prevention programs effectively reduce property crimes by 28% and juvenile delinquency by 35% in urban barangays. Community watch programs and youth engagement initiatives are the most impactful interventions.',
                'recommendations' => 'LGUs should allocate dedicated funding for community-based crime prevention programs. PNP should formalize partnerships with barangay watch groups. DILG should develop standardized training modules for community volunteers and expand youth mentorship programs.',
            ],
            [
                'title' => 'Medicinal Plant Utilization Among Indigenous Communities in Mindanao',
                'college' => 'CHM',
                'abstract' => 'This research documents and scientifically evaluates traditional medicinal plant practices among indigenous communities in Mindanao.',
                'introduction' => 'Indigenous communities in Mindanao possess rich traditional knowledge of medicinal plants. This study aims to document these practices and evaluate the pharmacological properties of commonly used plants.',
                'methodology' => 'Ethnobotanical surveys were conducted with 150 traditional healers across 8 indigenous communities. 45 plant species were collected and subjected to phytochemical screening and antimicrobial assays.',
                'results' => '32 of 45 tested plants showed significant antimicrobial activity. 12 plants contained novel alkaloid compounds not previously documented. Traditional dosage practices were found to be within safe therapeutic ranges.',
                'discussion' => 'The indigenous medicinal plant knowledge represents a valuable resource for pharmaceutical research. Conservation of these plant species and protection of indigenous intellectual property rights are critical priorities.',
                'references' => "Balick, M. J., & Cox, P. A. (2020). Plants, people, and culture: The science of ethnobotany (2nd ed.). Garland Science.\nFarnsworth, N. R., & Soejarto, D. D. (1991). Global importance of medicinal plants. In O. Akerele, V. Heywood, & H. Synge (Eds.), The conservation of medicinal plants (pp. 25–51). Cambridge University Press.\nNewman, D. J., & Cragg, G. M. (2020). Natural products as sources of new drugs over the nearly four decades from 01/1981 to 09/2019. Journal of Natural Products, 83(3), 770–803. https://doi.org/10.1021/acs.jnatprod.9b01285",
                'conclusion' => 'Indigenous medicinal plant knowledge in Mindanao is scientifically validated, with 32 of 45 tested plants showing significant antimicrobial activity and 12 containing novel alkaloid compounds. Traditional dosage practices fall within safe therapeutic ranges.',
                'recommendations' => 'DENR should prioritize conservation of identified medicinal plant species. DOST should fund further pharmacological studies on the 12 novel alkaloid compounds. NCIP should help establish intellectual property protections for indigenous traditional knowledge holders.',
            ],
        ];

        foreach ($papers as $i => $paper) {
            $college = $colleges->where('code', $paper['college'])->first();
            $student = $students->get($i % $students->count());
            $category = $categories->get($i % $categories->count());

            Research::create([
                'title' => $paper['title'],
                'abstract' => $paper['abstract'],
                'introduction' => $paper['introduction'],
                'methodology' => $paper['methodology'],
                'results' => $paper['results'],
                'discussion' => $paper['discussion'],
                'references' => $paper['references'] ?? null,
                'conclusion' => $paper['conclusion'] ?? null,
                'recommendations' => $paper['recommendations'] ?? null,
                'keywords' => 'research, education, Philippines, technology, innovation',
                'authors' => $student ? $student->name . ', et al.' : 'Unknown Author',
                'college_id' => $college ? $college->id : $colleges->first()->id,
                'category_id' => $category->id,
                'user_id' => $student ? $student->id : User::where('role', 'student')->first()->id,
                'publication_year' => rand(2020, 2025),
            ]);
        }
    }
}
