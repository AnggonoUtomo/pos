export type PageGuideContent = {
    title: string;
    description: string;
    sections: { title: string; steps: string[] }[];
    notes: string[];
};
