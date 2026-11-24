users {
    id auto increment not null primary key
    name varchar
    email varchar unique not null
    password varchar
    role enum(admin, agent, user)
    avatar_url
}

tickets {
    id auto increment not null primary key
    ticket_number varchar
    user_id int foreign key(users.id) not null
    admin_id nullable
    agent_id nullable
    title varchar
    description text
    priority enum (low, medium, high)
    status (open, in_progress, pending_review, resolved, closed)
}

ticket_milestones {
    id auto increment not null primary key
    ticket_id int foreign key(tickets.id) not null
    created_by 
    title varchar
    description text
    status enum(pending, completed),
    due_date date
    completed_at date
}

conversations {
    id auto increment not null primary key
    ticket_id int foreign key(tickets.id) not null
    type (enum: user_admin, agent_admin)
}

message {
    id
    conversation_id int foreign key(conversations.id) not null
    sender_id 
    body text
    attachment_url nullable
    read_at nullable
    created_at
}

notifications