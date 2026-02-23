-- ========================================
-- FULIZABOOST SUPABASE DATABASE SETUP
-- ========================================

-- Run these SQL statements in Supabase SQL Editor
-- https://supabase.com/dashboard/project/hpjsryfgqwdhpibkpbqk/sql

-- ========================================
-- TRANSACTIONS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS public.transactions (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    phone TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0,
    limit_amount DECIMAL(10,2) DEFAULT 0,
    checkout_request_id TEXT UNIQUE,
    mpesa_receipt TEXT,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'timeout')),
    result_desc TEXT,
    transaction_date TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Enable RLS
ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;

-- Create policy for anon access
CREATE POLICY "Allow public read access" 
ON public.transactions FOR SELECT 
TO anon, authenticated 
USING (true);

CREATE POLICY "Allow service role full access" 
ON public.transactions FOR ALL 
TO service_role 
USING (true)
WITH CHECK (true);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_transactions_checkout_id 
ON public.transactions(checkout_request_id);

CREATE INDEX IF NOT EXISTS idx_transactions_phone 
ON public.transactions(phone);

CREATE INDEX IF NOT EXISTS idx_transactions_status 
ON public.transactions(status);

-- ========================================
-- LIVE FEED TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS public.live_feed (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    phone_masked TEXT,
    amount_boosted DECIMAL(10,2),
    user_initial TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Enable RLS
ALTER TABLE public.live_feed ENABLE ROW LEVEL SECURITY;

-- Create policy for anon access
CREATE POLICY "Allow public read access" 
ON public.live_feed FOR SELECT 
TO anon, authenticated 
USING (true);

CREATE POLICY "Allow service role full access" 
ON public.live_feed FOR ALL 
TO service_role 
USING (true)
WITH CHECK (true);

-- Create index
CREATE INDEX IF NOT EXISTS idx_live_feed_created 
ON public.live_feed(created_at DESC);

-- ========================================
-- TRIGGER TO UPDATE UPDATED_AT
-- ========================================

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_transactions_updated_at
    BEFORE UPDATE ON public.transactions
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ========================================
-- SAMPLE DATA (Optional - for testing)
-- ========================================

-- Insert sample live feed data
INSERT INTO public.live_feed (phone_masked, amount_boosted, user_initial) VALUES
('0721****77', 15000, 'J'),
('0723****12', 9500, 'M'),
('0710****90', 4200, 'D'),
('0722****33', 5600, 'S'),
('0728****01', 21300, 'M');
