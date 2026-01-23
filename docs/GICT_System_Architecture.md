# GICT Institute - System Architecture Visualization

## 1. System Architecture Diagram

```mermaid
graph TB
    subgraph "Frontend Layer"
        A[Homepage] --> B[Login/Registration]
        B --> C[Student Dashboard]
        B --> D[Faculty Dashboard]
        B --> E[Admin Dashboard]
        C --> F[Course Enrollment]
        C --> G[Certificate View]
        D --> H[Course Management]
        E --> I[Enrollment Approvals]
        E --> J[Marks Management]
        E --> K[Certificate Generation]
    end
    
    subgraph "Backend Layer"
        L[PHP Application] --> M[Session Manager]
        L --> N[Database Layer]
        L --> O[File Manager]
        M --> P[Authentication]
        M --> Q[Authorization]
    end
    
    subgraph "Database Layer"
        R[(MySQL Database)]
        S[Users Table]
        T[Courses Table]
        U[Enrollments Table]
        V[Payments Table]
        W[Certificates Table]
        R --> S
        R --> T
        R --> U
        R --> V
        R --> W
    end
    
    subgraph "External Services"
        X[ImgBB API]
        Y[QR Code Generator]
        Z[File Storage]
    end
    
    A --> L
    C --> L
    D --> L
    E --> L
    L --> R
    O --> X
    O --> Y
    O --> Z
```

## 2. User Flow Diagram

```mermaid
flowchart TD
    A[User Visits Homepage] --> B{User Type?}
    B -->|New User| C[Registration]
    B -->|Existing User| D[Login]
    C --> E[Email Verification]
    E --> D
    D --> F{Login Successful?}
    F -->|No| G[Show Error]
    F -->|Yes| H{User Role?}
    H -->|Student| I[Student Dashboard]
    H -->|Faculty| J[Faculty Dashboard]
    H -->|Admin| K[Admin Dashboard]
    
    I --> L[Browse Courses]
    L --> M[Select Course]
    M --> N[Enrollment Form]
    N --> O[Payment Selection]
    O --> P[Submit Enrollment]
    P --> Q[Payment Pending Status]
    
    K --> R[Review Payments]
    R --> S{Payment Valid?}
    S -->|Yes| T[Approve Enrollment]
    S -->|No| U[Reject Payment]
    T --> V[Student Enrolled]
    U --> W[Enrollment Cancelled]
    
    V --> X[Course Completion]
    X --> Y[Enter Marks]
    Y --> Z[Generate Certificate]
    Z --> AA[Student Downloads Certificate]
```

## 3. Database Entity Relationship Diagram

```mermaid
erDiagram
    USERS {
        int id PK
        string username UK
        string password
        string full_name
        string email
        string phone
        string address
        string profile_image
        enum user_type
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    COURSES {
        int id PK
        string name
        text description
        string category
        string duration
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    SUB_COURSES {
        int id PK
        int course_id FK
        string name
        text description
        decimal fee
        string duration
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    STUDENT_ENROLLMENTS {
        int id PK
        int user_id FK
        int sub_course_id FK
        date enrollment_date
        date completion_date
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    PAYMENTS {
        int id PK
        int user_id FK
        int sub_course_id FK
        decimal amount
        date payment_date
        string payment_method
        string transaction_id
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    CERTIFICATES {
        int id PK
        int enrollment_id FK
        string certificate_number
        string certificate_url
        string marksheet_url
        enum status
        timestamp generated_at
    }
    
    STUDENT_MARKS {
        int id PK
        int enrollment_id FK
        int subject_id FK
        int marks_obtained
        int max_marks
        string grade
        timestamp created_at
    }
    
    COURSE_SUBJECTS {
        int id PK
        int sub_course_id FK
        string subject_name
        int max_marks
        enum status
        timestamp created_at
    }
    
    USERS ||--o{ STUDENT_ENROLLMENTS : "enrolls in"
    USERS ||--o{ PAYMENTS : "makes"
    COURSES ||--o{ SUB_COURSES : "contains"
    SUB_COURSES ||--o{ STUDENT_ENROLLMENTS : "enrolled in"
    SUB_COURSES ||--o{ PAYMENTS : "paid for"
    SUB_COURSES ||--o{ COURSE_SUBJECTS : "has subjects"
    STUDENT_ENROLLMENTS ||--o{ CERTIFICATES : "generates"
    STUDENT_ENROLLMENTS ||--o{ STUDENT_MARKS : "has marks"
    COURSE_SUBJECTS ||--o{ STUDENT_MARKS : "graded in"
```

## 4. User Journey Map

```mermaid
journey
    title Student Journey Through GICT Institute
    section Discovery
      Visit Homepage: 5: Student
      Browse Courses: 4: Student
      Read Course Details: 5: Student
    section Registration
      Create Account: 3: Student
      Verify Email: 2: Student
      Complete Profile: 4: Student
    section Enrollment
      Select Course: 5: Student
      Fill Enrollment Form: 3: Student
      Choose Payment Method: 4: Student
      Submit Payment: 3: Student
    section Waiting
      Wait for Approval: 2: Student
      Receive Confirmation: 5: Student
    section Learning
      Access Course Materials: 4: Student
      Track Progress: 4: Student
    section Completion
      Complete Course: 5: Student
      Receive Certificate: 5: Student
      Download Documents: 5: Student
```

## 5. Feature Priority Matrix

```mermaid
quadrantChart
    title Feature Priority Matrix
    x-axis Low Effort --> High Effort
    y-axis Low Impact --> High Impact
    
    quadrant-1 High Impact, Low Effort
    quadrant-2 High Impact, High Effort
    quadrant-3 Low Impact, Low Effort
    quadrant-4 Low Impact, High Effort
    
    User Authentication: [0.2, 0.9]
    Course Enrollment: [0.3, 0.9]
    Payment Processing: [0.4, 0.8]
    Certificate Generation: [0.6, 0.9]
    Mobile Responsiveness: [0.3, 0.7]
    Admin Dashboard: [0.5, 0.8]
    File Upload System: [0.4, 0.6]
    News Management: [0.2, 0.4]
    Gallery System: [0.2, 0.3]
    Advanced Analytics: [0.8, 0.6]
    Video Integration: [0.9, 0.5]
    Multi-language: [0.7, 0.4]
```

## 6. Technology Stack Visualization

```mermaid
graph LR
    subgraph "Frontend"
        A[HTML5]
        B[CSS3]
        C[JavaScript ES6]
        D[Bootstrap]
    end
    
    subgraph "Backend"
        E[PHP 7.4+]
        F[PDO]
        G[Session Management]
        H[File Handling]
    end
    
    subgraph "Database"
        I[MySQL 5.7+]
        J[Database Schema]
        K[Indexes]
        L[Relationships]
    end
    
    subgraph "External APIs"
        M[ImgBB API]
        N[QR Code Generator]
        O[Email Services]
    end
    
    subgraph "Infrastructure"
        P[Apache/Nginx]
        Q[SSL Certificate]
        R[File Storage]
        S[Backup System]
    end
    
    A --> E
    B --> E
    C --> E
    D --> E
    E --> I
    F --> I
    G --> I
    H --> M
    H --> N
    E --> O
    E --> P
    P --> Q
    I --> R
    I --> S
```

## 7. Security Architecture

```mermaid
graph TB
    subgraph "Security Layers"
        A[User Input Validation]
        B[Authentication Layer]
        C[Authorization Layer]
        D[Data Encryption]
        E[Session Management]
        F[File Upload Security]
    end
    
    subgraph "Threat Protection"
        G[SQL Injection Prevention]
        H[XSS Protection]
        I[CSRF Protection]
        J[File Upload Validation]
        K[Password Hashing]
        L[Session Security]
    end
    
    subgraph "Access Control"
        M[Role-Based Access]
        N[Permission Matrix]
        O[Route Protection]
        P[API Security]
    end
    
    A --> G
    B --> K
    C --> M
    D --> H
    E --> L
    F --> J
    M --> N
    N --> O
    O --> P
```

## How to Use These Visualizations

### 1. **Mermaid Live Editor**
- Visit: https://mermaid.live/
- Copy and paste any of the diagrams above
- Export as PNG, SVG, or PDF

### 2. **VS Code Extension**
- Install "Mermaid Preview" extension
- Create `.md` files with mermaid code blocks
- Preview directly in VS Code

### 3. **GitHub/GitLab**
- These diagrams will render automatically in markdown files
- Perfect for documentation and README files

### 4. **Notion/Obsidian**
- Both support Mermaid diagrams
- Great for project documentation

### 5. **Draw.io/Lucidchart**
- Import the concepts and recreate with more customization
- Professional presentation-ready diagrams

Would you like me to create any specific type of visualization or modify any of these diagrams?
