Activity 2: Conceptual Model of a System

Section: BSIS - 207

Members:
- Asis, Marvin Guardiana
- Bernesto, Harvie Loresco
- Datiles, Igi Boy Angeles
- Faustino, Angelo Brian R.
- Labiano Jr., Lito Largueza
- Macatangay, Jian Guyo


TITLE: "Design and Development of the Good Spot Gaming Hub Console Shop Management System in Don Placido Avenue, Dasmariñas"


DESCRIPTION:

It is about developing a website for Good Spot Gaming Hub — a console shop with PS5 and Xbox Series X for customers to rent hourly, open time, or unlimited, and also hosting monthly tournaments. The website will function as an online management system that organizes rentals, monitors console usage, and keeps digital records of transactions. It will also help improve the overall efficiency and professionalism of the shop's operations.


PURPOSE:

The purpose of our system is to let customers see what available units (PS5, Xbox) are currently available to play on. During playtime they would be able to see the number of hours and minutes they have left. To advertise the shop even having updating features on newly released games or having a customer service where they can request what games they want to be installed. The system also aims to automate the computation of rental time and total payment to reduce manual errors. Additionally, it will provide organized reports that will help the owner monitor daily sales and overall business performance.


USERS:

The users of our system are the customer, the current shopkeeper, and the owner of Good Spot Gaming Hub. Our most popular customers are college students from De La Salle and EAC. Other customers tend to range from the age of 22-30. The shopkeeper is the one who inserts the rental details of the customer, monitors the time of each console being used, updates the availability of units, and manages tournament registrations. The owner of Good Spot Gaming Hub can monitor daily sales, check rental records, track console usage, and view reports to help in decision-making for the business.


AUTOMATED PROCESSES:

The processes that our system will automate or improve include the time convenience at time tracking record, monitoring of available PS5 and Xbox Series X units, automatic computation of rental hours and fees, and recording of customer transactions. The system will also improve advertisement of newly released games, manage customer game installation requests, and organize monthly tournament registrations. Additionally, it will generate reports for sales and console usage to make the shop's operations more organized, efficient, and accurate.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CONCEPTUAL MODEL

┌───────────────────────────────────────────────────────────────────────────────┐
│                           CONCEPTUAL MODEL                                  │
│                                                                             │
│    INPUT                   PROCESS                  OUTPUT                  │
│                                                                             │
│  • Date                 • Check Console           "Design and Development   │
│  • Unit Number            Availability             of the Good Spot Gaming  │
│    (PS5 / Xbox          • Record Rental            Hub Console Shop         │
│     Series X)             Transaction              Management System in     │
│  • Start Time           • Assign Console Unit      Don Placido Avenue,      │
│  • End Time             • Start and Track          Dasmariñas."             │
│  • Rental Mode            Playtime                                          │
│    (Hourly / Open       • Compute Rental Fee                                │
│     Time / Unlimited)   • Update Remaining                                  │
│  • Additional             Time                                              │
│    Requests             • Process Payment                                   │
│    (extra hours,        • Update Console Status                             │
│     controller            (Available / In Use)                              │
│     rental, etc.)       • Store Transaction                                 │
│  • Payment Details        Records                                           │
│  • Game Installation    • Generate Reports                                  │
│    Requests             • Post Tournament                                   │
│  • Tournament             Announcement                                      │
│    Announcement         • Update Tournament                                 │
│    Details                Status                                            │
│                                                                             │
│                          EVALUATION ◄──────────────────┘                    │
└───────────────────────────────────────────────────────────────────────────────┘


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CONCEPTUAL MODEL — DATABASE TABLE MAPPING

This section maps every element from the conceptual model to its corresponding database table:

┌────────────────────────────────────────┬──────────────────────────────────────┐
│ CONCEPTUAL MODEL ELEMENT               │ DATABASE TABLE(S)                    │
├────────────────────────────────────────┼──────────────────────────────────────┤
│ INPUT: Date, Unit Number,              │ gaming_sessions, consoles            │
│   Start/End Time, Rental Mode          │                                      │
│                                        │                                      │
│ INPUT: Additional Requests             │ additional_requests                  │
│   (extra hours, controllers)           │                                      │
│                                        │                                      │
│ INPUT: Payment Details                 │ transactions                         │
│                                        │                                      │
│ INPUT: Game Installation Requests      │ game_requests                        │
│                                        │                                      │
│ INPUT: Tournament Announcement Details │ tournaments,                         │
│                                        │ tournament_participants              │
├────────────────────────────────────────┼──────────────────────────────────────┤
│ PROCESS: Check Console Availability    │ consoles.status                      │
│                                        │                                      │
│ PROCESS: Record Rental Transaction     │ transactions                         │
│                                        │                                      │
│ PROCESS: Assign Console Unit           │ gaming_sessions.console_id           │
│                                        │                                      │
│ PROCESS: Start & Track Playtime        │ gaming_sessions                      │
│                                        │ (start_time, end_time, duration)     │
│                                        │                                      │
│ PROCESS: Compute Rental Fee            │ gaming_sessions.total_cost           │
│                                        │ (auto-computed)                      │
│                                        │                                      │
│ PROCESS: Update Console Status         │ consoles.status                      │
│                                        │ (available / in_use / maintenance)   │
│                                        │                                      │
│ PROCESS: Store Transaction Records     │ transactions                         │
│                                        │                                      │
│ PROCESS: Generate Reports              │ reports                              │
│                                        │                                      │
│ PROCESS: Post Tournament Announcement  │ tournaments.announcement             │
│                                        │                                      │
│ PROCESS: Update Tournament Status      │ tournaments.status                   │
├────────────────────────────────────────┼──────────────────────────────────────┤
│ OUTPUT / EVALUATION:                   │ reports, aggregated queries          │
│   Reports, Sales, Usage Data           │                                      │
└────────────────────────────────────────┴──────────────────────────────────────┘


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DATABASE SCHEMA — 11 TABLES


TABLE 1: users
Purpose: All system users (Customer / Shopkeeper / Owner)

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  user_id         │ INT, PK, AUTO_INCREMENT                    │
  email           │ VARCHAR(100), UNIQUE                       │ Login credential
  password_hash   │ VARCHAR(255)                               │ bcrypt hashed
  full_name       │ VARCHAR(100)                               │
  phone           │ VARCHAR(20)                                │
  role            │ ENUM('customer','shopkeeper','owner')      │ Access control
  status          │ ENUM('active','inactive')                  │ Default: active
  created_at      │ DATETIME                                   │ Default: NOW()


TABLE 2: consoles
Purpose: PS5 and Xbox Series X units

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  console_id      │ INT, PK, AUTO_INCREMENT                    │
  console_name    │ VARCHAR(50)                                │ e.g. "PS5 Unit 1"
  console_type    │ ENUM('PS5','Xbox Series X')                │ Only 2 types
  unit_number     │ VARCHAR(10), UNIQUE                        │ e.g. "PS5-01"
  status          │ ENUM('available','in_use','maintenance')   │ Real-time status
  hourly_rate     │ DECIMAL(10,2)                              │ Rate in ₱
  created_at      │ DATETIME                                   │


TABLE 3: gaming_sessions
Purpose: Rental sessions with time tracking

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  session_id      │ INT, PK, AUTO_INCREMENT                    │
  user_id         │ INT, FK → users                            │ Customer
  console_id      │ INT, FK → consoles                         │ Assigned unit
  rental_mode     │ ENUM('hourly','open_time','unlimited')     │ From conceptual model
  start_time      │ DATETIME                                   │ Session start
  end_time        │ DATETIME, NULLABLE                         │ NULL while active
  duration_minutes│ INT, NULLABLE                              │ Auto-computed on end
  hourly_rate     │ DECIMAL(10,2)                              │ Rate at time of session
  total_cost      │ DECIMAL(10,2), NULLABLE                    │ Auto-computed
  status          │ ENUM('active','completed','cancelled')     │
  created_by      │ INT, FK → users                            │ Shopkeeper who created
  created_at      │ DATETIME                                   │


TABLE 4: additional_requests
Purpose: Extra hours, controller rentals, etc.

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  request_id      │ INT, PK, AUTO_INCREMENT                    │
  session_id      │ INT, FK → gaming_sessions                  │ Linked to session
  request_type    │ ENUM('extra_hours','controller_rental',    │
                  │       'other')                             │
  description     │ TEXT                                       │ Details
  extra_cost      │ DECIMAL(10,2)                              │ Added to session total
  status          │ ENUM('pending','approved','denied')        │
  created_at      │ DATETIME                                   │


TABLE 5: transactions
Purpose: Payment records

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  transaction_id  │ INT, PK, AUTO_INCREMENT                    │
  session_id      │ INT, FK → gaming_sessions                  │
  user_id         │ INT, FK → users                            │ Customer
  amount          │ DECIMAL(10,2)                              │ Total paid in ₱
  payment_method  │ ENUM('cash','gcash','credit_card')         │
  payment_status  │ ENUM('pending','completed','failed')       │
  transaction_date│ DATETIME                                   │
  processed_by    │ INT, FK → users                            │ Shopkeeper
  created_at      │ DATETIME                                   │


TABLE 6: games
Purpose: Game library for each console

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  game_id         │ INT, PK, AUTO_INCREMENT                    │
  game_name       │ VARCHAR(150)                               │
  console_type    │ ENUM('PS5','Xbox Series X','Both')         │
  genre           │ VARCHAR(50)                                │
  is_available    │ TINYINT(1)                                 │ Default: 1
  is_new_release  │ TINYINT(1)                                 │ For advertisement
  description     │ TEXT                                       │
  cover_image     │ VARCHAR(255)                               │ File path
  added_date      │ DATE                                       │


TABLE 7: game_requests
Purpose: Customer game installation requests

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  gr_id           │ INT, PK, AUTO_INCREMENT                    │
  user_id         │ INT, FK → users                            │ Customer
  game_name       │ VARCHAR(150)                               │ Requested game title
  console_type    │ ENUM('PS5','Xbox Series X')                │
  message         │ TEXT                                       │ Optional details
  status          │ ENUM('pending','approved',                 │
                  │       'installed','denied')                │
  created_at      │ DATETIME                                   │
  resolved_at     │ DATETIME, NULLABLE                         │


TABLE 8: tournaments
Purpose: Monthly tournament management

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  tournament_id   │ INT, PK, AUTO_INCREMENT                    │
  tournament_name │ VARCHAR(150)                               │
  game_id         │ INT, FK → games                            │ Featured game
  console_type    │ ENUM('PS5','Xbox Series X')                │
  start_date      │ DATETIME                                   │
  end_date        │ DATETIME                                   │
  entry_fee       │ DECIMAL(10,2)                              │ In ₱
  prize_pool      │ DECIMAL(10,2)                              │
  max_participants│ INT                                        │
  status          │ ENUM('upcoming','ongoing',                 │
                  │       'completed','cancelled')             │
  announcement    │ TEXT                                       │ Public text
  created_at      │ DATETIME                                   │


TABLE 9: tournament_participants
Purpose: Tournament registrations

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  participant_id  │ INT, PK, AUTO_INCREMENT                    │
  tournament_id   │ INT, FK → tournaments                      │
  user_id         │ INT, FK → users                            │
  registration_date│ DATETIME                                  │
  payment_status  │ ENUM('pending','paid')                     │ Entry fee status
  placement       │ INT, NULLABLE                              │ Final rank
  prize_amount    │ DECIMAL(10,2), NULLABLE                    │ Won amount


TABLE 10: reports
Purpose: Generated reports for owner

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  report_id       │ INT, PK, AUTO_INCREMENT                    │
  report_type     │ ENUM('daily_sales','rental_records',       │
                  │       'console_usage','tournament')        │
  generated_by    │ INT, FK → users                            │ Owner/Shopkeeper
  date_from       │ DATE                                       │ Report range start
  date_to         │ DATE                                       │ Report range end
  file_path       │ VARCHAR(255), NULLABLE                     │ Exported file path
  created_at      │ DATETIME                                   │


TABLE 11: system_settings
Purpose: Shop configuration

  Column          │ Type                                       │ Notes
  ────────────────┼────────────────────────────────────────────┼──────────────────────
  setting_id      │ INT, PK, AUTO_INCREMENT                    │
  setting_key     │ VARCHAR(50), UNIQUE                        │ e.g. ps5_hourly_rate
  setting_value   │ TEXT                                       │
  description     │ VARCHAR(255)                               │
  updated_at      │ DATETIME                                   │
